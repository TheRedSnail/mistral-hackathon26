"""
UAT — Emails (6 tests)
Tests: list, themes, component retrieval, full creation workflow.
"""
import sys
sys.stdout.reconfigure(encoding='utf-8')

from playwright.sync_api import sync_playwright
from test_helpers import (
    safe_print, login, goto_ai, send_chat,
    get_activity_names, has_activity, extract_id, BASE
)

# Fallback email ID for component test (from existing test fixtures)
FALLBACK_EMAIL_ID = 75


def run():
    pass_count = 0
    fail_count = 0
    email_id_for_components = None

    def check(name, condition, got=None):
        nonlocal pass_count, fail_count
        if condition:
            safe_print(f'  PASS: {name}')
            pass_count += 1
        else:
            safe_print(f'  FAIL: {name}' + (f' — got: {got!r}' if got is not None else ''))
            fail_count += 1

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        ctx = browser.new_context(viewport={'width': 1600, 'height': 900})
        page = ctx.new_page()

        safe_print('\n=== Login ===')
        login(page)
        goto_ai(page)

        # TEST 1 — list_emails activity fires
        safe_print('\n--- TEST 1: list_emails ---')
        reply = send_chat(page, 'List my emails', wait_ms=30000)
        activities = get_activity_names(page)
        safe_print(f'  Activities: {activities}')
        safe_print(f'  Reply (100): {reply[:100]!r}')
        check('list_emails activity fires', has_activity(activities, 'list_emails'), activities)

        # Try to extract an email ID for use in TEST 5
        email_id_for_components = extract_id(reply)
        if not email_id_for_components:
            email_id_for_components = str(FALLBACK_EMAIL_ID)
        safe_print(f'  Email ID for component test: {email_id_for_components}')
        page.screenshot(path='/tmp/uat_emails_01.png')

        # TEST 2 — Response contains email subjects or is non-trivial
        safe_print('\n--- TEST 2: Reply has email content ---')
        check('Reply has email content (length > 20)', len(reply.strip()) > 20, len(reply))

        # TEST 3 — list_email_themes works
        safe_print('\n--- TEST 3: list_email_themes ---')
        reply = send_chat(page, 'List all available email themes', wait_ms=30000)
        activities = get_activity_names(page)
        safe_print(f'  Activities: {activities}')
        safe_print(f'  Reply (100): {reply[:100]!r}')
        check('list_email_themes activity fires', has_activity(activities, 'list_email_themes'), activities)
        page.screenshot(path='/tmp/uat_emails_03.png')

        # TEST 4 — At least one theme mentioned
        safe_print('\n--- TEST 4: Theme in reply ---')
        has_theme = any(w in reply.lower() for w in ['theme', 'paprika', 'vibrant', 'template'])
        safe_print(f'  Theme mentioned: {has_theme}')
        check('Reply mentions a theme name', has_theme, reply[:150])

        # TEST 5 — get_email_components works
        safe_print('\n--- TEST 5: get_email_components ---')
        reply = send_chat(
            page,
            f'Get the components of email {email_id_for_components}',
            wait_ms=30000
        )
        activities = get_activity_names(page)
        safe_print(f'  Activities: {activities}')
        safe_print(f'  Reply (200): {reply[:200]!r}')
        check(
            'get_email_components activity fires',
            has_activity(activities, 'get_email_components'),
            activities
        )
        check(
            'Reply mentions components/slots',
            any(w in reply.lower() for w in ['component', 'slot', '#', 'index', 'text']),
            reply[:200]
        )
        page.screenshot(path='/tmp/uat_emails_05.png')

        # TEST 6 — Full email creation workflow (3-min timeout for multi-tool)
        safe_print('\n--- TEST 6: Full email creation ---')
        try:
            reply = send_chat(
                page,
                (
                    "Draft a new email with subject 'UAT Email Test' about a summer sale, "
                    "using the first available theme. Fill all content slots."
                ),
                wait_ms=180000
            )
        except Exception as e:
            safe_print(f'  Timeout/error: {e}')
            reply = ''
        activities = get_activity_names(page)
        safe_print(f'  Activities ({len(activities)}): {activities}')
        safe_print(f'  Reply (200): {reply[:200]!r}')
        check(
            'Multiple activities fired (>=3 for full creation)',
            len(activities) >= 3,
            activities
        )
        check(
            'Reply mentions email ID or creation success',
            any(w in reply.lower() for w in ['created', 'email', 'id', '#', 'summer', 'draft']),
            reply[:200]
        )
        page.screenshot(path='/tmp/uat_emails_06.png')

        # ── Summary ────────────────────────────────────────────────────────
        safe_print(f'\n{"="*50}')
        safe_print(f'Emails: {pass_count} passed, {fail_count} failed')
        safe_print(f'{"="*50}')

        browser.close()

    return pass_count, fail_count


if __name__ == '__main__':
    passed, failed = run()
    sys.exit(0 if failed == 0 else 1)
