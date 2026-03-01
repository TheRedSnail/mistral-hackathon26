"""
UAT — Core UI (9 tests)
Tests: page load, welcome screen, iframe, divider, status bar, basic chat flow.
"""
import sys
sys.stdout.reconfigure(encoding='utf-8')

from playwright.sync_api import sync_playwright
from test_helpers import safe_print, login, goto_ai, send_chat, BASE


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

        # ── Login ──────────────────────────────────────────────────────────
        safe_print('\n=== Login ===')
        login(page)

        # ── Navigate to AI page ────────────────────────────────────────────
        safe_print('\n=== Navigate to Odradek AI ===')
        page.goto(f'{BASE}/s/odradek/ai')
        page.wait_for_load_state('networkidle')
        page.wait_for_timeout(1000)

        # TEST 1 — Page loads
        safe_print('\n--- TEST 1: Page loads ---')
        title = page.title()
        safe_print(f'  Page title: {title!r}')
        check('Page loads (title or 200)', 'Odradek' in title or len(title) > 0, title)
        page.screenshot(path='/tmp/uat_core_01.png')

        # TEST 2 — Welcome screen visible before any chat
        safe_print('\n--- TEST 2: Welcome screen ---')
        welcome = page.query_selector('#odradek-welcome')
        welcome_visible = welcome is not None and welcome.is_visible()
        safe_print(f'  #odradek-welcome visible: {welcome_visible}')
        check('Welcome screen visible before chat', welcome_visible)
        page.screenshot(path='/tmp/uat_core_02.png')

        # TEST 3 — Mautic iframe present and has src
        safe_print('\n--- TEST 3: Mautic iframe ---')
        iframe_src = page.evaluate(
            "document.getElementById('odradek-mautic-frame')?.src || ''"
        )
        safe_print(f'  iframe src: {iframe_src!r}')
        check('Mautic iframe present with src', len(iframe_src) > 0, iframe_src)

        # TEST 4 — Resize divider present
        safe_print('\n--- TEST 4: Resize divider ---')
        divider = page.query_selector('#odradek-divider')
        check('Resize divider #odradek-divider in DOM', divider is not None)

        # TEST 5 — Status bar shows idle initially
        safe_print('\n--- TEST 5: Status bar idle ---')
        is_idle = page.evaluate(
            "document.getElementById('odradek-status-bar')?.classList.contains('status-idle') ?? false"
        )
        safe_print(f'  status-idle class: {is_idle}')
        check('Status bar has status-idle initially', is_idle)

        # TEST 6 — Send "hello", exchange card appears
        safe_print('\n--- TEST 6: Exchange card appears after chat ---')
        page.fill('#odradek-input', 'hello')
        page.click('#odradek-send')
        # Wait for at least one exchange card to appear (don't wait for full response)
        try:
            page.wait_for_selector('.exchange-card', timeout=10000)
            card_appeared = True
        except Exception:
            card_appeared = False
        safe_print(f'  Exchange card appeared: {card_appeared}')
        check('Exchange card appears after send', card_appeared)

        # Wait for the full response before proceeding
        try:
            page.wait_for_selector('#odradek-send[disabled]', timeout=3000)
        except Exception:
            pass
        try:
            page.wait_for_selector('#odradek-send:not([disabled])', timeout=45000)
            page.wait_for_timeout(500)
        except Exception:
            pass

        cards = page.eval_on_selector_all('.exchange-card', "els => els.length")
        safe_print(f'  Exchange card count: {cards}')
        check('At least 1 exchange card present', cards >= 1, cards)
        page.screenshot(path='/tmp/uat_core_06.png')

        # TEST 7 — AI responds with non-empty text
        safe_print('\n--- TEST 7: AI response non-empty ---')
        from test_helpers import get_last_ai_reply
        reply = get_last_ai_reply(page)
        safe_print(f'  Reply (first 100): {reply[:100]!r}')
        check('AI reply is non-empty', len(reply.strip()) > 0, reply)

        # TEST 8 — Status bar busy during request
        safe_print('\n--- TEST 8: Status busy during request ---')
        page.fill('#odradek-input', 'What is the capital of France?')
        page.click('#odradek-send')
        # Immediately poll for busy state
        busy_detected = False
        try:
            page.wait_for_selector('#odradek-status-bar.status-busy', timeout=5000)
            busy_detected = True
        except Exception:
            busy_detected = False
        safe_print(f'  status-busy detected: {busy_detected}')
        check('Status bar shows status-busy during request', busy_detected)

        # Wait for completion
        try:
            page.wait_for_selector('#odradek-send[disabled]', timeout=3000)
        except Exception:
            pass
        try:
            page.wait_for_selector('#odradek-send:not([disabled])', timeout=45000)
            page.wait_for_timeout(300)
        except Exception:
            pass

        # TEST 9 — Status returns idle after completion
        safe_print('\n--- TEST 9: Status returns idle ---')
        is_idle_after = page.evaluate(
            "document.getElementById('odradek-status-bar')?.classList.contains('status-idle') ?? false"
        )
        safe_print(f'  status-idle after completion: {is_idle_after}')
        check('Status bar returns to status-idle after response', is_idle_after)
        page.screenshot(path='/tmp/uat_core_09.png')

        # ── Summary ────────────────────────────────────────────────────────
        safe_print(f'\n{"="*50}')
        safe_print(f'Core UI: {pass_count} passed, {fail_count} failed')
        safe_print(f'{"="*50}')

        browser.close()

    return pass_count, fail_count


if __name__ == '__main__':
    passed, failed = run()
    sys.exit(0 if failed == 0 else 1)
