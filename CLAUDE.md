# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is **OdradekAIBundle** — a Mautic marketing automation plugin that embeds a Mistral AI assistant into Mautic. Users interact with the AI via a split-screen UI (Mautic iframe + chat panel) and the AI can autonomously manage Mautic entities (contacts, emails, campaigns, segments, reports) through function calling.

## Development Setup

Start the full stack (Mautic 5 + MySQL):

```bash
docker compose up -d
```

Mautic runs at `http://localhost:8080`. The plugin directory is volume-mounted — PHP changes take effect immediately (no rebuild needed). After enabling the plugin in Mautic, configure it at **Settings → Configuration → AI Settings**.

To clear Mautic's cache after PHP changes:
```bash
docker compose exec mautic php bin/console cache:clear
```

To enter the Mautic container:
```bash
docker compose exec mautic bash
```

## Architecture

### Plugin Structure

Standard Mautic plugin (`AbstractPluginBundle`) under namespace `MauticPlugin\OdradekAIBundle`. Service wiring is fully autowired via `Config/services.php`. Plugin metadata and routes are defined in `Config/config.php`.

**Two routes:**
- `GET /odradek/ai` → `AiController::indexAction` — renders the full-page split-screen UI (bypasses Mautic chrome; returns a raw `Response`)
- `POST /odradek/ai/chat` → `ChatController::chatAction` — SSE streaming endpoint for the agentic loop

### Agentic Loop (Backend)

`ChatController` orchestrates the agentic loop (max 10 iterations):
1. Builds a system message with page context (current URL, selected text, visible text)
2. Calls `MistralClient::complete()` with conversation history + tool schemas
3. Emits SSE events: `content`, `tool_call`, `tool_result`, `client_tool`, `plan`, `error`, `done`
4. Executes tool calls via `MauticToolExecutor`, appends results to message history, and loops

**Plan mode** (when `planMode=true` and `approved=false`): uses a separate system prompt to generate a JSON plan `{"steps": [...]}`, emits a `plan` SSE event, and stops. The frontend shows an approve/cancel UI; on approval, sends the same messages with `approved=true` to actually execute.

### Tools

`ToolDefinitions::getTools()` returns the Mistral function-calling schemas. `MauticToolExecutor::execute()` runs them:

- **Server-side** (PHP, call Mautic models directly): `list_contacts`, `get_contact`, `create_contact`, `update_contact`, `delete_contact`, `list_emails`, `get_email`, `create_email`, `update_email`, `list_campaigns`, `get_campaign`, `list_segments`, `create_segment`, `list_reports`, `get_report_data`
- **Client-side** (handled in `odradek-ai.js`): `navigate_mautic` (changes iframe `src`), `get_page_info` (injects context chip)

To add a new tool: add the schema to `ToolDefinitions::getTools()`, add the handler to `MauticToolExecutor::execute()`, and if client-side, handle the `client_tool` SSE event in `odradek-ai.js`.

### Frontend (`Assets/js/odradek-ai.js`)

Single IIFE, no build step. Key features:
- **Drag-to-resize divider** between the Mautic iframe pane and AI chat pane
- **Element selector mode**: overlays highlights on iframe elements; clicking captures element text + CSS path as a context chip
- **Context chips**: page captures and selected elements are merged into the `context` payload sent to the backend
- **SSE via fetch + ReadableStream** (not `EventSource`): POST body required, so native `EventSource` is opened then immediately closed; a `fetch` call handles the stream manually
- **Auto-reload**: after any mutating tool call (`create_*`, `update_*`, `delete_*`), the iframe reloads 400ms after `done`

### Configuration Parameters

Stored in Mautic's `local.php` via the config form:

| Parameter | Default |
|---|---|
| `odradek_ai_api_key` | `''` |
| `odradek_ai_model` | `mistral-large-latest` |
| `odradek_ai_enabled` | `false` |
| `odradek_ai_max_tokens` | `8000` |

The `max_tokens` parameter is currently stored but not forwarded to the Mistral API payload in `MistralClient`.

### Twig Templates

Two parallel template directories exist — `Resources/views/` and `Views/` — both containing the same templates for Mautic version compatibility. The `AiController` references `@OdradekAI/Ai/index.html.twig` (resolved by Mautic's Twig loader from `Resources/views/`).
