"""
UAT — Segments CRUD (5 tests)
Tests: list, create with filter, get, update, filter fields.
Creates a test segment named 'UAT Playwright Segment' (no delete tool — name it clearly).
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
    segment_id = None

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

        # TEST 1 — list_segments activity fires
        safe_print('\n--- TEST 1: list_segments ---')
        reply = send_chat(page, 'List all contact segments', wait_ms=30000)
        activities = get_activity_names(page)
        safe_print(f'  Activities: {activities}')
        safe_print(f'  Reply (100): {reply[:100]!r}')
        check('list_segments activity fires', has_activity(activities, 'list_segments'), activities)
        page.screenshot(path='/tmp/uat_segments_01.png')

        # TEST 2 — create_segment with filter
        safe_print('\n--- TEST 2: create_segment with filter ---')
        reply = send_chat(
            page,
            "Define a new audience segment named 'UAT Playwright Segment' targeting contacts from Austria",
            wait_ms=30000
        )
        activities = get_activity_names(page)
        safe_print(f'  Activities: {activities}')
        safe_print(f'  Reply (200): {reply[:200]!r}')
        check('create_segment activity fires', has_activity(activities, 'create_segment'), activities)

        segment_id = extract_id(reply)
        safe_print(f'  Extracted segment_id: {segment_id!r}')
        check('Segment ID extracted from reply', segment_id is not None, reply[:200])
        page.screenshot(path='/tmp/uat_segments_02.png')

        # TEST 3 — get_segment returns filter data
        safe_print('\n--- TEST 3: get_segment with filters ---')
        if segment_id:
            reply = send_chat(
                page,
                f'Get segment {segment_id} and show me its filters',
                wait_ms=30000
            )
            activities = get_activity_names(page)
            safe_print(f'  Activities: {activities}')
            safe_print(f'  Reply (200): {reply[:200]!r}')
            check(
                'get_segment returns filter/country data',
                any(w in reply.lower() for w in ['austria', 'filter', 'country', 'field']),
                reply[:200]
            )
        else:
            safe_print('  SKIP: no segment_id from TEST 2')
            check('get_segment returns filter/country data', False, 'skipped — no segment_id')
        page.screenshot(path='/tmp/uat_segments_03.png')

        # TEST 4 — update_segment works
        safe_print('\n--- TEST 4: update_segment ---')
        if segment_id:
            reply = send_chat(
                page,
                f"Update segment {segment_id}: change description to 'UAT test segment - safe to delete'",
                wait_ms=30000
            )
            activities = get_activity_names(page)
            safe_print(f'  Activities: {activities}')
            safe_print(f'  Reply (150): {reply[:150]!r}')
            check('update_segment activity fires', has_activity(activities, 'update_segment'), activities)
        else:
            safe_print('  SKIP: no segment_id')
            check('update_segment activity fires', False, 'skipped')
        page.screenshot(path='/tmp/uat_segments_04.png')

        # TEST 5 — get_segment_filter_fields tool
        safe_print('\n--- TEST 5: get_segment_filter_fields ---')
        reply = send_chat(
            page,
            'What fields can I filter contacts by when creating segments?',
            wait_ms=30000
        )
        activities = get_activity_names(page)
        safe_print(f'  Activities: {activities}')
        safe_print(f'  Reply (200): {reply[:200]!r}')
        check(
            'get_segment_filter_fields activity fires',
            has_activity(activities, 'get_segment_filter_fields'),
            activities
        )
        check(
            'Reply mentions filterable fields',
            any(w in reply.lower() for w in ['email', 'country', 'firstname', 'first name', 'field']),
            reply[:200]
        )
        page.screenshot(path='/tmp/uat_segments_05.png')

        # ── Summary ────────────────────────────────────────────────────────
        safe_print(f'\n{"="*50}')
        safe_print(f'Segments: {pass_count} passed, {fail_count} failed')
        safe_print(f'{"="*50}')

        browser.close()

    return pass_count, fail_count


if __name__ == '__main__':
    passed, failed = run()
    sys.exit(0 if failed == 0 else 1)
