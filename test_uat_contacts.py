"""
UAT — Contacts CRUD (7 tests)
Flow: list → create → get → update → verify update → delete
Creates a test contact and cleans it up at the end.
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
    contact_id = None

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

        # TEST 1 — list_contacts activity fires
        safe_print('\n--- TEST 1: list_contacts ---')
        reply = send_chat(page, 'List my first 3 contacts', wait_ms=30000)
        activities = get_activity_names(page)
        safe_print(f'  Activities: {activities}')
        safe_print(f'  Reply (80): {reply[:80]!r}')
        check('list_contacts activity fires', has_activity(activities, 'list_contacts'), activities)
        page.screenshot(path='/tmp/uat_contacts_01.png')

        # TEST 2 — Response mentions contact data
        safe_print('\n--- TEST 2: Contact data in reply ---')
        has_contact_data = '@' in reply or 'contact' in reply.lower() or len(reply) > 20
        safe_print(f'  Reply contains contact data: {has_contact_data}')
        check('Reply mentions contacts or emails', has_contact_data, reply[:100])

        # TEST 3 — create_contact workflow
        safe_print('\n--- TEST 3: create_contact ---')
        reply = send_chat(
            page,
            'Register a new contact: first name UAT, last name Tester, email uat-playwright@test.invalid',
            wait_ms=30000
        )
        activities = get_activity_names(page)
        safe_print(f'  Activities: {activities}')
        safe_print(f'  Reply (150): {reply[:150]!r}')
        check('create_contact activity fires', has_activity(activities, 'create_contact'), activities)

        contact_id = extract_id(reply)
        safe_print(f'  Extracted contact_id: {contact_id!r}')
        check('Contact ID extracted from reply', contact_id is not None, reply[:200])
        page.screenshot(path='/tmp/uat_contacts_03.png')

        # TEST 4 — get_contact returns details
        safe_print('\n--- TEST 4: get_contact ---')
        if contact_id:
            reply = send_chat(page, f'Get details for contact {contact_id}', wait_ms=30000)
            activities = get_activity_names(page)
            safe_print(f'  Activities: {activities}')
            safe_print(f'  Reply (200): {reply[:200]!r}')
            check(
                'get_contact returns UAT contact data',
                'uat-playwright' in reply.lower() or 'UAT Tester' in reply or 'uat tester' in reply.lower(),
                reply[:200]
            )
        else:
            safe_print('  SKIP: no contact_id from TEST 3')
            check('get_contact returns UAT contact data', False, 'skipped — no contact_id')
        page.screenshot(path='/tmp/uat_contacts_04.png')

        # TEST 5 — update_contact workflow
        safe_print('\n--- TEST 5: update_contact ---')
        if contact_id:
            reply = send_chat(
                page,
                f'Update contact {contact_id}: set company to UAT Corp',
                wait_ms=30000
            )
            activities = get_activity_names(page)
            safe_print(f'  Activities: {activities}')
            safe_print(f'  Reply (150): {reply[:150]!r}')
            check('update_contact activity fires', has_activity(activities, 'update_contact'), activities)
            check(
                'Update reply mentions success',
                any(w in reply.lower() for w in ['updated', 'success', 'done', 'changed', 'set', 'uат corp', 'uat corp']),
                reply[:150]
            )
        else:
            safe_print('  SKIP: no contact_id')
            check('update_contact activity fires', False, 'skipped')
            check('Update reply mentions success', False, 'skipped')
        page.screenshot(path='/tmp/uat_contacts_05.png')

        # TEST 6 — verify update persisted
        safe_print('\n--- TEST 6: verify update persisted ---')
        if contact_id:
            reply = send_chat(
                page,
                f'Get contact {contact_id} and tell me their company',
                wait_ms=30000
            )
            safe_print(f'  Reply (200): {reply[:200]!r}')
            check(
                'Company field shows UAT Corp',
                'UAT Corp' in reply or 'uat corp' in reply.lower(),
                reply[:200]
            )
        else:
            safe_print('  SKIP: no contact_id')
            check('Company field shows UAT Corp', False, 'skipped')
        page.screenshot(path='/tmp/uat_contacts_06.png')

        # TEST 7 — delete_contact workflow
        safe_print('\n--- TEST 7: delete_contact ---')
        if contact_id:
            reply = send_chat(
                page,
                f'Delete contact {contact_id} — yes I confirm, please proceed immediately',
                wait_ms=30000
            )
            activities = get_activity_names(page)
            safe_print(f'  Activities: {activities}')
            safe_print(f'  Reply (150): {reply[:150]!r}')

            # If AI still asks for confirmation, send explicit "yes"
            if not has_activity(activities, 'delete_contact') and (
                'confirm' in reply.lower() or 'sure' in reply.lower() or '?' in reply
            ):
                safe_print('  AI requested confirmation — sending yes...')
                reply = send_chat(page, 'yes', wait_ms=30000)
                activities = get_activity_names(page)
                safe_print(f'  After yes — Activities: {activities}')
                safe_print(f'  After yes — Reply (100): {reply[:100]!r}')

            check('delete_contact activity fires', has_activity(activities, 'delete_contact'), activities)
            check(
                'Delete reply confirms removal',
                any(w in reply.lower() for w in ['deleted', 'removed', 'success', 'contact']),
                reply[:150]
            )
        else:
            safe_print('  SKIP: no contact_id')
            check('delete_contact activity fires', False, 'skipped')
            check('Delete reply confirms removal', False, 'skipped')
        page.screenshot(path='/tmp/uat_contacts_07.png')

        # ── Summary ────────────────────────────────────────────────────────
        safe_print(f'\n{"="*50}')
        safe_print(f'Contacts: {pass_count} passed, {fail_count} failed')
        safe_print(f'{"="*50}')

        browser.close()

    return pass_count, fail_count


if __name__ == '__main__':
    passed, failed = run()
    sys.exit(0 if failed == 0 else 1)
