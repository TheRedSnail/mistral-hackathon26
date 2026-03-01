# OdradekAIBundle

A Mautic 5 plugin that embeds a Mistral AI assistant into Mautic. Users interact with the AI via a split-screen UI (Mautic iframe + chat panel) and the AI can autonomously manage Mautic entities (contacts, emails, campaigns, segments, reports) through function calling.

## Requirements

- Docker + Docker Compose
- Python 3 (for the data seeder — stdlib only, no pip installs needed)

## Quick Start

### 1. Start the stack

```bash
docker compose up -d
```

Mautic runs at [http://localhost:8080](http://localhost:8080). The plugin directory is volume-mounted — PHP changes take effect immediately.

### 2. Install and activate the plugin

```bash
docker compose exec mautic bash -c "cd /var/www/html && php bin/console mautic:plugins:reload --env=prod -v"
docker compose exec mautic bash -c "rm -rf /var/www/html/var/cache/prod/* && cd /var/www/html && php bin/console cache:warmup --env=prod"
```

Then in the Mautic UI:

1. **Settings → Plugins → Install/Upgrade**
2. **Settings → Configuration → AI Settings** — enter your Mistral API key and save
3. Navigate to `/s/odradek/ai`

### 3. Seed dummy data (optional but recommended)

A fresh Mautic install has no contacts or engagement data. Run the seeder to populate realistic test data:

```bash
python scripts/seed_mautic.py
```

This inserts directly into MySQL via `docker exec` — no API auth needed. It adds:

| Entity | Count |
|---|---|
| Companies | 25 (varied industries, EU + US) |
| Contacts | 150 (realistic names, emails, locations) |
| Segments | 8 (Newsletter, Hot Leads, Customers, Cold, Austrian, SMB, Enterprise, Churned) |
| Email templates | 5 (draft entities) |
| Email stats | ~400 sends, ~160 opens (40% open rate) |
| Point change logs | ~200 events |

The seeder is **additive** — safe to run multiple times. Each run appends a new batch.

After seeding, clear Mautic's cache so the UI reflects the new data:

```bash
docker compose exec mautic php bin/console cache:clear
```

To see segment member counts populated in the UI, run the segment update cron once:

```bash
docker compose exec mautic php bin/console mautic:segments:update --env=prod
```

Verify the counts with:

```bash
docker exec hackathon-db-1 mysql -umautic -pmautic mautic -e \
  "SELECT 'contacts' t, COUNT(*) n FROM leads
   UNION SELECT 'companies', COUNT(*) FROM companies
   UNION SELECT 'email_stats', COUNT(*) FROM email_stats
   UNION SELECT 'segments', COUNT(*) FROM lead_lists WHERE is_global=1;"
```

> **Note:** Contacts inserted directly into MySQL must have `date_identified` set, otherwise Mautic treats them as anonymous visitors and hides them from the Contacts list. The seeder handles this automatically.

## Development

Clear the cache after PHP changes:

```bash
docker compose exec mautic php bin/console cache:clear
```

For config/service changes (forces full container rebuild):

```bash
docker compose exec mautic bash -c "rm -rf /var/www/html/var/cache/prod/* && php bin/console cache:warmup --env=prod"
```

Enter the Mautic container:

```bash
docker compose exec mautic bash
```

## Configuration

Set in **Settings → Configuration → AI Settings**:

| Parameter | Default | Description |
|---|---|---|
| `odradek_ai_api_key` | _(empty)_ | Mistral API key |
| `odradek_ai_model` | `mistral-large-latest` | Model to use |
| `odradek_ai_enabled` | `false` | Enable/disable the plugin |
| `odradek_ai_max_tokens` | `8000` | Max tokens per response |
| `odradek_ai_gemini_api_key` | _(empty)_ | Gemini API key (image generation) |

## Routes

| Method | Path | Description |
|---|---|---|
| GET | `/s/odradek/ai` | Split-screen AI assistant UI |
| POST | `/s/odradek/ai/chat` | SSE streaming chat endpoint |
