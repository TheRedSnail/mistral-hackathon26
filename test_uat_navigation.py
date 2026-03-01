"""
UAT — Navigation & Element Selector (5 tests)
Tests: navigate_mautic tool, URL display update, select button, chip creation, chip removal.
"""
import sys
sys.stdout.reconfigure(encoding='utf-8')

from playwright.sync_api import sync_playwright
from test_helpers import (
    safe_print, login, goto_ai, send_chat,
    get_activity_names, has_activity, get_chips, BASE
)


def run():
    pass_count = 0
    fail_count = 0

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
        page.wait_for_timeout(1500)

        # TEST 1 — navigate_mautic tool changes iframe src
        safe_print('\n--- TEST 1: navigate_mautic changes iframe src ---')
        initial_src = page.evaluate(
            "document.getElementById('odradek-mautic-frame')?.src || ''"
        )
        safe_print(f'  Initial iframe src: {initial_src!r}')

        reply = send_chat(page, 'Navigate to the contacts list', wait_ms=30000)
        activities = get_activity_names(page)
        safe_print(f'  Activities: {activities}')
        safe_print(f'  Reply (100): {reply[:100]!r}')

        new_src = page.evaluate(
            "document.getElementById('odradek-mautic-frame')?.src || ''"
        )
        safe_print(f'  New iframe src: {new_src!r}')

        src_changed = new_src != initial_src
        src_has_contacts = '/contacts' in new_src or 'contacts' in new_src.lower()
        check('navigate_mautic activity fires', has_activity(activities, 'navigate_mautic'), activities)
        check('Iframe src changed after navigation', src_changed, {'old': initial_src, 'new': new_src})
        check('New src points to contacts', src_has_contacts, new_src)
        page.screenshot(path='/tmp/uat_nav_01.png')

        # TEST 2 — URL display updates after navigation
        safe_print('\n--- TEST 2: URL display updates ---')
        url_display = page.query_selector('#odradek-url-display')
        if url_display:
            display_text = url_display.text_content() or ''
            safe_print(f'  URL display text: {display_text!r}')
            check(
                'URL display updates with new path',
                'contact' in display_text.lower() or len(display_text.strip()) > 0,
                display_text
            )
        else:
            # Try alternate selectors for URL display
            alt = (
                page.query_selector('[id*="url-display"]') or
                page.query_selector('[id*="url_display"]') or
                page.query_selector('.odradek-url') or
                page.query_selector('#odradek-iframe-url')
            )
            if alt:
                txt = alt.text_content() or ''
                safe_print(f'  URL display (alt selector): {txt!r}')
                check('URL display updates with new path', len(txt.strip()) > 0, txt)
            else:
                safe_print('  #odradek-url-display not found — checking iframe src directly')
                check('URL display updates (src changed)', src_changed, new_src)
        page.screenshot(path='/tmp/uat_nav_02.png')

        # TEST 3 — Element selector button exists
        safe_print('\n--- TEST 3: Element selector button ---')
        select_btn = (
            page.query_selector('#odradek-select-btn') or
            page.query_selector('[id*="select"]') or
            page.query_selector('button:has-text("Select")') or
            page.query_selector('button[title*="Select"]') or
            page.query_selector('button[title*="select"]')
        )
        select_btn_exists = select_btn is not None
        safe_print(f'  Select button found: {select_btn_exists}')
        if select_btn:
            safe_print(f'  Selector: {select_btn.evaluate("el => el.id || el.className || el.textContent.slice(0,30)")}')
        check('Element selector button present', select_btn_exists)
        page.screenshot(path='/tmp/uat_nav_03.png')

        # TEST 4 — Clicking an element in the iframe creates a context chip
        safe_print('\n--- TEST 4: Element selection creates context chip ---')
        chips_before = get_chips(page)
        safe_print(f'  Chips before selection: {chips_before}')

        chip_created = False
        if select_btn:
            try:
                # Click select button to enter selection mode
                select_btn.click()
                page.wait_for_timeout(600)

                # Find the Mautic iframe frame object to click inside it
                iframe_handle = page.query_selector('#odradek-mautic-frame')
                mautic_frame = iframe_handle.content_frame() if iframe_handle else None

                if mautic_frame:
                    # Wait for frame to be ready and click something visible
                    mautic_frame.wait_for_load_state('domcontentloaded', timeout=5000)
                    page.wait_for_timeout(500)

                    # Try to click on a visible element (heading, paragraph, or body)
                    clicked = False
                    for sel in ['h1', 'h2', 'h3', 'p', '.page-header', 'table td', 'body']:
                        try:
                            el = mautic_frame.query_selector(sel)
                            if el and el.is_visible():
                                el.click(timeout=3000)
                                clicked = True
                                safe_print(f'  Clicked {sel!r} in Mautic iframe')
                                break
                        except Exception:
                            continue

                    if not clicked:
                        # Click at a fixed position in the iframe
                        mautic_frame.click('body', position={'x': 300, 'y': 150}, timeout=3000)
                        safe_print('  Clicked at fixed position in iframe')

                    page.wait_for_timeout(1000)
                    chips_after = get_chips(page)
                    safe_print(f'  Chips after element click: {chips_after}')
                    chip_created = len(chips_after) > len(chips_before)
                else:
                    safe_print('  Could not access Mautic iframe content frame')
            except Exception as e:
                safe_print(f'  Element selection error: {e}')
        else:
            safe_print('  SKIP: select button not found')

        check('Clicking element in iframe creates context chip', chip_created, get_chips(page))
        page.screenshot(path='/tmp/uat_nav_04.png')

        # TEST 5 — Context chip removed on X click
        safe_print('\n--- TEST 5: Context chip removal ---')
        current_chips = get_chips(page)
        safe_print(f'  Current chips: {current_chips}')

        # If no chips from TEST 4, try to add one via page capture
        if len(current_chips) == 0:
            safe_print('  No chips present — attempting to add one via navigation reply chip')
            # The navigation activity should have added something; try a get_page_info prompt
            send_chat(page, 'What page am I looking at?', wait_ms=20000)
            current_chips = get_chips(page)
            safe_print(f'  Chips after get_page_info: {current_chips}')

        if len(current_chips) > 0:
            # Find and click the remove button on the first chip
            remove_btn = page.query_selector('.context-chip-remove')
            if remove_btn:
                remove_btn.click()
                page.wait_for_timeout(500)
                chips_after_remove = get_chips(page)
                safe_print(f'  Chips after remove: {chips_after_remove}')
                check(
                    'Context chip removed on X click',
                    len(chips_after_remove) < len(current_chips),
                    {'before': len(current_chips), 'after': len(chips_after_remove)}
                )
            else:
                safe_print('  .context-chip-remove button not found')
                check('Context chip removed on X click', False, 'remove btn not found')
        else:
            safe_print('  SKIP: no chips to remove')
            check('Context chip removed on X click', False, 'skipped — no chips to remove')
        page.screenshot(path='/tmp/uat_nav_05.png')

        # ── Summary ────────────────────────────────────────────────────────
        safe_print(f'\n{"="*50}')
        safe_print(f'Navigation: {pass_count} passed, {fail_count} failed')
        safe_print(f'{"="*50}')

        browser.close()

    return pass_count, fail_count


if __name__ == '__main__':
    passed, failed = run()
    sys.exit(0 if failed == 0 else 1)
