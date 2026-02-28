<?php

declare(strict_types=1);

namespace MauticPlugin\OdradekAIBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\OdradekAIBundle\Service\MauticToolExecutor;
use MauticPlugin\OdradekAIBundle\Service\MistralClient;
use MauticPlugin\OdradekAIBundle\Service\ToolDefinitions;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends CommonController
{
    public function __construct(
        private readonly MistralClient      $mistralClient,
        private readonly MauticToolExecutor $toolExecutor,
    ) {}

    public function chatAction(Request $request): StreamedResponse
    {
        $body     = json_decode($request->getContent(), true) ?? [];
        $messages = $body['messages'] ?? [];
        $context  = $body['context']  ?? [];
        $approved = $body['approved'] ?? false;
        $planMode = $body['planMode'] ?? false;

        $mistral  = $this->mistralClient;
        $executor = $this->toolExecutor;

        return new StreamedResponse(function () use ($messages, $context, $approved, $planMode, $mistral, $executor) {
            // Disable all output buffering so SSE events flush immediately.
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            ini_set('output_buffering', 'off');

            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('X-Accel-Buffering: no');
            header('Connection: keep-alive');

            $emitSse = function (string $event, array $data) {
                echo "event: {$event}\n";
                echo 'data: ' . json_encode($data) . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            };

            try {
                $systemMsg = $this->buildSystemMessage($context);
                $fullMsgs  = array_merge([$systemMsg], $messages);

                // ── Plan Mode ─────────────────────────────────────────────────
                if ($planMode && !$approved) {
                    $planSystemMsg = [
                        'role'    => 'system',
                        'content' => 'You are a planning assistant. The user wants to perform a task in Mautic. '
                            . 'Respond ONLY with a valid JSON object in this exact format, no other text: '
                            . '{"steps": ["Step 1 description", "Step 2 description", ...]}. '
                            . 'List 3-6 concrete steps the AI will take to complete the task.',
                    ];

                    $planResponse = $mistral->complete([$planSystemMsg, ...$messages], []);
                    $planContent  = $planResponse['content'] ?? '{}';

                    // Extract JSON even if wrapped in markdown code fences
                    if (preg_match('/\{.*\}/s', $planContent, $m)) {
                        $planContent = $m[0];
                    }

                    $planData = json_decode($planContent, true);
                    $steps    = $planData['steps'] ?? ['Could not generate plan. Please try again.'];

                    $emitSse('plan', ['steps' => $steps]);
                    $emitSse('done', []);
                    return;
                }

                // ── Agentic Loop ──────────────────────────────────────────────
                $tools   = ToolDefinitions::getTools();
                $maxIter = 10;

                // When multiple GrapesJS components are selected, force tool use on the
                // first turn so the AI cannot fabricate results with a text-only response.
                $selectedComponents = $context['selectedComponents'] ?? [];
                $forceToolOnFirst   = count($selectedComponents) > 1;

                $emitSse('thinking', []);
                for ($i = 0; $i < $maxIter; $i++) {
                    $toolChoice = ($i === 0 && $forceToolOnFirst) ? 'required' : 'auto';
                    $response   = $mistral->complete($fullMsgs, $tools, $toolChoice);

                    // Emit text content if any
                    if (!empty($response['content'])) {
                        $emitSse('content', ['text' => $response['content']]);
                    }

                    // No tool calls → we're done
                    if (empty($response['tool_calls'])) {
                        $emitSse('done', []);
                        return;
                    }

                    $calls          = $response['tool_calls'];
                    $callCount      = count($calls);
                    $toolResultMsgs = [];

                    if ($callCount === 1) {
                        // ── Single tool: existing behaviour ──────────────────────────────────
                        $call     = $calls[0];
                        $toolName = $call['function']['name'];
                        $toolArgs = json_decode($call['function']['arguments'], true) ?? [];
                        $callId   = $call['id'];

                        $emitSse('tool_call', ['name' => $toolName, 'args' => $toolArgs, 'id' => $callId]);
                        $result = $executor->execute($toolName, $toolArgs);

                        if (!empty($result['client_side'])) {
                            $emitSse('client_tool', ['tool' => $toolName, 'args' => $toolArgs, 'id' => $callId]);
                            $toolResultMsgs[] = ['role' => 'tool', 'content' => 'Client-side tool executed.', 'tool_call_id' => $callId];
                        } else {
                            $emitSse('tool_result', ['tool' => $toolName, 'result' => $result, 'id' => $callId]);
                            $toolResultMsgs[] = ['role' => 'tool', 'content' => json_encode($result), 'tool_call_id' => $callId];
                        }

                    } else {
                        // ── Batch: N > 1 tool calls ───────────────────────────────────────────
                        static $batchSeq = 0;
                        $batchId = 'batch-' . (++$batchSeq);

                        $emitSse('batch_start', [
                            'batchId'  => $batchId,
                            'total'    => $callCount,
                            'toolName' => $calls[0]['function']['name'],
                        ]);

                        $successCount = 0;
                        $failCount    = 0;
                        $completed    = 0;

                        foreach ($calls as $call) {
                            $toolName = $call['function']['name'];
                            $toolArgs = json_decode($call['function']['arguments'], true) ?? [];
                            $callId   = $call['id'];
                            $result   = $executor->execute($toolName, $toolArgs);
                            $completed++;

                            $isClientSide = !empty($result['client_side']);
                            if ($isClientSide) {
                                $successCount++;
                                $summary = 'Client-side tool executed.';
                                $ok      = true;
                                // Emit client_tool so the frontend JS can apply the change (e.g. update GrapesJS component)
                                $emitSse('client_tool', ['tool' => $toolName, 'args' => $toolArgs, 'id' => $callId]);
                            } else {
                                $ok = ($result['success'] ?? true) !== false;
                                $ok ? $successCount++ : $failCount++;
                                $summary = $result['message'] ?? $result['error'] ?? json_encode($result);
                                if (strlen($summary) > 80) $summary = substr($summary, 0, 77) . '...';
                            }

                            $emitSse('batch_progress', [
                                'batchId'   => $batchId,
                                'completed' => $completed,
                                'total'     => $callCount,
                                'callId'    => $callId,
                                'toolName'  => $toolName,
                                'keyArg'    => self::extractKeyArg($toolName, $toolArgs),
                                'args'      => $toolArgs,
                                'success'   => $ok,
                                'summary'   => $summary,
                            ]);

                            $toolResultMsgs[] = [
                                'role'         => 'tool',
                                'content'      => $isClientSide ? 'Client-side tool executed.' : json_encode($result),
                                'tool_call_id' => $callId,
                            ];
                        }

                        $emitSse('batch_done', [
                            'batchId'      => $batchId,
                            'total'        => $callCount,
                            'successCount' => $successCount,
                            'failCount'    => $failCount,
                        ]);
                    }

                    // Append assistant turn + tool results, then continue loop
                    $fullMsgs[] = [
                        'role'       => 'assistant',
                        'content'    => $response['content'] ?? '',
                        'tool_calls' => $response['tool_calls'],
                    ];
                    $fullMsgs = array_merge($fullMsgs, $toolResultMsgs);
                }

                $emitSse('done', []);
            } catch (\Throwable $e) {
                $emitSse('error', ['message' => $e->getMessage()]);
                $emitSse('done', []);
            }
        });
    }

    private static function extractKeyArg(string $toolName, array $args): string
    {
        return match ($toolName) {
            'create_contact', 'update_contact' => trim(
                ($args['fields']['firstname'] ?? '') . ' ' . ($args['fields']['lastname'] ?? '')
            ) ?: ($args['fields']['email'] ?? ''),
            'delete_contact', 'get_contact'             => '#' . ($args['id'] ?? '?'),
            'create_email', 'update_email', 'get_email' => $args['name'] ?? ('#' . ($args['id'] ?? '?')),
            'get_email_components'   => '#' . ($args['id'] ?? '?'),
            'update_email_component' => '#' . ($args['id'] ?? '?') . '[' . ($args['componentIndex'] ?? '?') . ']',
            'create_segment'  => $args['name'] ?? '',
            'navigate_mautic' => $args['path'] ?? '',
            default           => '',
        };
    }

    private function buildSystemMessage(array $context): array
    {
        $content  = "You are an AI assistant embedded inside the Mautic marketing automation platform. ";
        $content .= "You have access to tools to manage contacts, emails, campaigns, segments, and reports. ";
        $content .= "Always use tools to perform actions — never fabricate data. ";
        $content .= "Be concise, action-oriented, and always confirm before deleting anything. ";
        $content .= "When you delete data, ask conversationally first (e.g. \"Shall I delete contact #42?\") and only call delete_contact after the user says yes. ";
        $content .= "ETHICS GUARDRAIL: Whenever you create an email or the user asks you to review email content, ";
        $content .= "automatically call analyze_email_ethics on the content before saving — do this proactively without being asked. ";
        $content .= "If the ethics score is below 70 or any critical/high severity issues are found, warn the user and suggest fixes before proceeding. ";
        $content .= "When the user asks about campaign results, performance, or insights, use analyze_campaign_performance. ";
        $content .= "When planning a new campaign sequence or email journey, use suggest_campaign_journey. ";
        $content .= "When creating a full email, follow this sequence: "
                  . "(1) Call list_email_themes and pick a fitting theme. "
                  . "(2) Call create_email with the chosen template and body='' (empty) — the theme provides the structure. "
                  . "(3) Call get_email_components with the new email ID to see all text slots (index + current placeholder). "
                  . "(4) Write targeted content for each relevant slot. "
                  . "     Skip any slot whose current text contains Mautic tokens "
                  . "     ({unsubscribe_text}, {webview_text}, {signature}, {contactfield=...}) "
                  . "     or looks like a legal/footer line — leave those unchanged. "
                  . "(5) Call update_email_component once per slot you are filling. "
                  . "(6) Call navigate_mautic with path '/s/emails/edit/{id}' so the user can preview the result. "
                  . "Always provide HTML as inner content only (headings, paragraphs, links, lists) — never a full HTML document. ";
        $content .= "When your response requires the user to make a choice or provide an answer before you can proceed, "
                  . "end your message with the marker [ASK]: on its own line, followed immediately by your question or numbered options. "
                  . "Use [ASK]: only when you genuinely cannot continue without user input. "
                  . "Do not use [ASK]: for rhetorical questions, offers of further help, or confirmations after completing an action. ";
        $content .= "When context.selectedComponents is present, prefer update_grapesjs_component "
                  . "for in-place edits (translate, rewrite, replace copy) rather than update_email. ";

        if (!empty($context['url'])) {
            $title = $context['pageTitle'] ?? '';
            $content .= "The user is currently viewing: {$context['url']}" . ($title ? " ({$title})" : '') . ". ";
        }

        if (!empty($context['selectedText'])) {
            $content .= "The user has selected this text: \"{$context['selectedText']}\". ";
        }

        if (!empty($context['visibleText'])) {
            $snippet = mb_substr($context['visibleText'], 0, 1500);
            $content .= "Page content preview: \"{$snippet}\". ";
        }

        // Handle selectedComponents (array, new format) or selectedComponent (legacy single)
        $components = $context['selectedComponents'] ?? null;
        if (!$components && !empty($context['selectedComponent'])) {
            $components = [$context['selectedComponent']]; // wrap legacy format
        }
        if (!empty($components)) {
            $count = count($components);
            if ($count === 1) {
                $type = $components[0]['type'] ?? 'component';
                $text = mb_substr($components[0]['text'] ?? '', 0, 400);
                $content .= "The user has selected a \"{$type}\" component (index 0) in the GrapesJS builder. ";
                if ($text) $content .= "Its current text content is: \"{$text}\". ";
                $content .= "You MUST call update_grapesjs_component (componentIndex 0) to apply any edit. "
                          . "Never describe the change in text without calling the tool — the builder will not update unless you call the tool. ";
            } else {
                $content .= "The user has selected {$count} components in the GrapesJS builder: ";
                foreach ($components as $i => $comp) {
                    $type = $comp['type'] ?? 'component';
                    $text = mb_substr($comp['text'] ?? '', 0, 200);
                    $content .= "#{$i} ({$type})" . ($text ? ": \"{$text}\"" : '') . "; ";
                }
                $content .= "You MUST call update_grapesjs_component once per component you want to change, "
                          . "using the correct componentIndex (0-based) each time. "
                          . "Call the tool {$count} times sequentially — one call per component. "
                          . "Never describe the changes in text without calling the tool for each one — "
                          . "the builder will not update unless the tool is called. ";
            }
        }

        return ['role' => 'system', 'content' => $content];
    }
}
