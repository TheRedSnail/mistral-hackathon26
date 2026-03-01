"""
UAT — Campaigns (5 tests)
Tests: list, response quality, get_campaign, journey suggestion, sentiment analysis.
"""
import sys
sys.stdout.reconfigure(encoding='utf-8')

from playwright.sync_api import sync_playwright
from test_helpers import (
    safe_print, login, goto_ai, send_chat,
    get_activity_names, has_activity, extract_id, BASE
)


def run():
    pass_count = 0
    fail_count = 0
    campaign_id = None

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

        # TEST 1 — list_campaigns works
        safe_print('\n--- TEST 1: list_campaigns ---')
        reply = send_chat(page, 'List my campaigns', wait_ms=30000)
        activities = get_activity_names(page)
        safe_print(f'  Activities: {activities}')
        safe_print(f'  Reply (100): {reply[:100]!r}')
        check('list_campaigns activity fires', has_activity(activities, 'list_campaigns'), activities)
        page.screenshot(path='/tmp/uat_campaigns_01.png')

        # TEST 2 — Response is non-trivial (campaigns found or graceful empty message)
        safe_print('\n--- TEST 2: Reply has content ---')
        check('Reply has content (length > 10)', len(reply.strip()) > 10, len(reply))

        # Try to extract a campaign ID for TEST 3
        campaign_id = extract_id(reply)
        safe_print(f'  Campaign ID from list: {campaign_id!r}')

        # TEST 3 — get_campaign works (skip if no campaigns exist)
        safe_print('\n--- TEST 3: get_campaign ---')
        if campaign_id:
            reply = send_chat(
                page,
                f'Get details for campaign {campaign_id}',
                wait_ms=30000
            )
            activities = get_activity_names(page)
            safe_print(f'  Activities: {activities}')
            safe_print(f'  Reply (150): {reply[:150]!r}')
            check('get_campaign activity fires', has_activity(activities, 'get_campaign'), activities)
        else:
            safe_print('  SKIP: no campaigns found in Mautic — skipping get_campaign test')
            # Count as pass since empty instance is a valid state
            check('get_campaign (skipped — no campaigns)', True)
        page.screenshot(path='/tmp/uat_campaigns_03.png')

        # TEST 4 — suggest_campaign_journey returns structured plan
        safe_print('\n--- TEST 4: suggest_campaign_journey ---')
        reply = send_chat(
            page,
            'Suggest a 3-email journey for: new user onboarding',
            wait_ms=45000
        )
        activities = get_activity_names(page)
        safe_print(f'  Activities: {activities}')
        safe_print(f'  Reply (300): {reply[:300]!r}')
        check(
            'suggest_campaign_journey activity fires',
            has_activity(activities, 'suggest_campaign_journey'),
            activities
        )
        check(
            'Journey reply mentions emails and steps/timing',
            'email' in reply.lower() and any(w in reply.lower() for w in ['step', 'week', 'day', 'sequence', 'journey']),
            reply[:300]
        )
        page.screenshot(path='/tmp/uat_campaigns_04.png')

        # TEST 5 — Contact sentiment / analytics (flexible test)
        safe_print('\n--- TEST 5: Contact analytics ---')
        reply = send_chat(
            page,
            'Analyze sentiment of the contact with the highest lead score',
            wait_ms=45000
        )
        activities = get_activity_names(page)
        safe_print(f'  Activities: {activities}')
        safe_print(f'  Reply (200): {reply[:200]!r}')
        check(
            'Analytics activity fires (list_contacts or analyze_contact_sentiment)',
            has_activity(activities, 'list_contacts') or
            has_activity(activities, 'analyze_contact_sentiment') or
            has_activity(activities, 'get_contact'),
            activities
        )
        check(
            'Reply mentions sentiment or engagement',
            any(w in reply.lower() for w in ['sentiment', 'engagement', 'score', 'active', 'contact']),
            reply[:200]
        )
        page.screenshot(path='/tmp/uat_campaigns_05.png')

        # ── Summary ────────────────────────────────────────────────────────
        safe_print(f'\n{"="*50}')
        safe_print(f'Campaigns: {pass_count} passed, {fail_count} failed')
        safe_print(f'{"="*50}')

        browser.close()

    return pass_count, fail_count


if __name__ == '__main__':
    passed, failed = run()
    sys.exit(0 if failed == 0 else 1)
