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

                for ($i = 0; $i < $maxIter; $i++) {
                    $response = $mistral->complete($fullMsgs, $tools);

                    // Emit text content if any
                    if (!empty($response['content'])) {
                        $emitSse('content', ['text' => $response['content']]);
                    }

                    // No tool calls → we're done
                    if (empty($response['tool_calls'])) {
                        $emitSse('done', []);
                        return;
                    }

                    // Process tool calls one at a time (parallel_tool_calls=false)
                    $toolResultMsgs = [];

                    foreach ($response['tool_calls'] as $call) {
                        $toolName = $call['function']['name'];
                        $toolArgs = json_decode($call['function']['arguments'], true) ?? [];
                        $callId   = $call['id'];

                        $emitSse('tool_call', [
                            'name' => $toolName,
                            'args' => $toolArgs,
                            'id'   => $callId,
                        ]);

                        $result = $executor->execute($toolName, $toolArgs);

                        if (!empty($result['client_side'])) {
                            // Frontend will handle navigation / page-info tools
                            $emitSse('client_tool', ['tool' => $toolName, 'args' => $toolArgs, 'id' => $callId]);
                            $toolResultMsgs[] = [
                                'role'         => 'tool',
                                'content'      => 'Client-side tool executed.',
                                'tool_call_id' => $callId,
                            ];
                        } else {
                            $emitSse('tool_result', ['tool' => $toolName, 'result' => $result, 'id' => $callId]);
                            $toolResultMsgs[] = [
                                'role'         => 'tool',
                                'content'      => json_encode($result),
                                'tool_call_id' => $callId,
                            ];
                        }
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

        return ['role' => 'system', 'content' => $content];
    }
}
