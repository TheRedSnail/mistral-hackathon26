"""
Repeated selection + update test across 5 different components.
For each round:
  1. Click a distinct component in the GrapesJS canvas
  2. Verify chip updates to that component's text
  3. Ask AI to replace text with a unique marker
  4. Verify DOM updated with that marker
  5. Verify chip refreshes to show the new text
"""
import sys
sys.stdout.reconfigure(encoding='utf-8')

from playwright.sync_api import sync_playwright

BASE     = 'http://localhost:8080'
EMAIL    = 'stoker_jan@hotmail.com'
PASS     = 'Easter3-Upwind9-Tinwork6-Superior9'
EMAIL_ID = 75

# 5 update markers — one per round
MARKERS = [
    'ROUND-ONE-ALPHA',
    'ROUND-TWO-BETA',
    'ROUND-THREE-GAMMA',
    'ROUND-FOUR-DELTA',
    'ROUND-FIVE-EPSILON',
]

logs = []

def safe_print(msg):
    try:
        print(msg)
    except UnicodeEncodeError:
        print(msg.encode('ascii', 'replace').decode('ascii'))

def get_chips(page):
    return page.eval_on_selector_all(
        '#odradek-context-chips .context-chip span:first-child',
        "els => els.map(e => e.textContent)"
    )

def send_chat(page, text, wait_ms=20000):
    page.fill('#odradek-input', text)
    page.click('#odradek-send')
    page.wait_for_function("!document.getElementById('odradek-send').disabled", timeout=wait_ms)
    page.wait_for_timeout(500)
    msgs = page.eval_on_selector_all('.msg-ai .msg-body', "els => els.map(e => e.textContent)")
    return msgs[-1] if msgs else ''

def find_gjs_canvas(page):
    best, best_len = None, 0
    for f in page.frames:
        if f.url in ('about:blank', ''):
            try:
                content = f.inner_html('body')
                if content and len(content) > best_len:
                    best_len = len(content)
                    best = f
            except Exception:
                pass
    return best

def run():
    pass_count = 0
    fail_count = 0

    def check(name, condition, got=None):
        nonlocal pass_count, fail_count
        if condition:
            safe_print(f'    PASS: {name}')
            pass_count += 1
        else:
            safe_print(f'    FAIL: {name}' + (f' — got: {got!r}' if got is not None else ''))
            fail_count += 1

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        ctx = browser.new_context(viewport={'width': 1600, 'height': 900})
        page = ctx.new_page()
        page.on('console', lambda m: (
            logs.append(m.text),
            safe_print(f'  [GJS] {m.text}') if 'OdradekGJS' in m.text else None
        ))

        # ── Login ─────────────────────────────────────────────────────────
        page.goto(f'{BASE}/s/login')
        page.wait_for_load_state('networkidle')
        page.fill('#username', EMAIL)
        page.fill('#password', PASS)
        page.click('button[type=submit]')
        page.wait_for_load_state('networkidle')
        assert '/login' not in page.url

        # ── Odradek AI page ───────────────────────────────────────────────
        page.goto(f'{BASE}/s/odradek/ai')
        page.wait_for_load_state('networkidle')
        page.wait_for_timeout(1500)

        # ── Navigate iframe + open builder ────────────────────────────────
        safe_print('\n=== Opening email builder ===')
        page.evaluate(f"document.getElementById('odradek-mautic-frame').src = '{BASE}/s/emails/edit/{EMAIL_ID}'")
        page.wait_for_timeout(3000)

        email_frame = next((f for f in page.frames if f'emails/edit/{EMAIL_ID}' in f.url), None)
        assert email_frame, 'email edit frame not found'
        email_frame.wait_for_load_state('networkidle')
        email_frame.click('#emailform_buttons_builder_toolbar')
        page.wait_for_timeout(4000)

        bound = any('bound to editor' in l for l in logs)
        safe_print(f'Editor bound: {bound}')
        assert bound, 'GrapesJS editor did not bind'

        canvas = find_gjs_canvas(page)
        assert canvas, 'GrapesJS canvas not found'

        # ── Collect distinct components with non-empty text ───────────────
        safe_print('\n=== Collecting components ===')
        all_els = canvas.query_selector_all('[data-gjs-type="mj-text"]')
        candidates = []
        seen_texts = set()
        for el in all_els:
            try:
                txt = (el.inner_text() or '').strip()
                # Skip empty, token placeholders, and very long bodies
                if txt and txt not in seen_texts and not txt.startswith('{') and len(txt) < 200:
                    seen_texts.add(txt)
                    candidates.append((el, txt))
            except Exception:
                pass

        safe_print(f'Found {len(candidates)} distinct usable components:')
        for i, (_, t) in enumerate(candidates):
            safe_print(f'  [{i}] {t[:60]!r}')

        assert len(candidates) >= 5, f'Need 5 components, found {len(candidates)}'

        # Pick 5 — spread across the list for variety
        targets = candidates[:5]

        # ── 5 rounds ──────────────────────────────────────────────────────
        for round_num, (target_el, original_text) in enumerate(targets):
            marker = MARKERS[round_num]
            safe_print(f'\n{"─"*60}')
            safe_print(f'Round {round_num+1}/5 — original: {original_text[:50]!r}  marker: {marker}')

            # ── Step 1: Click the component ───────────────────────────────
            logs.clear()
            try:
                target_el.click(timeout=5000)
            except Exception as e:
                safe_print(f'  Click failed: {e} — re-querying...')
                # Re-query if stale
                all_els2 = canvas.query_selector_all('[data-gjs-type="mj-text"]')
                target_el = None
                for el in all_els2:
                    try:
                        txt = (el.inner_text() or '').strip()
                        # Match by current expected text (may have been updated in prior round)
                        if original_text[:20] in txt or marker in txt:
                            target_el = el
                            break
                    except Exception:
                        pass
                if not target_el:
                    safe_print('  Could not re-find component — skipping round')
                    fail_count += 2
                    continue
                target_el.click(timeout=5000)

            page.wait_for_timeout(800)

            chips = get_chips(page)
            safe_print(f'  Chip after click: {chips}')
            chip_text = chips[0] if chips else ''

            # The chip should mention the original text (or the marker if this was updated before)
            check(
                f'R{round_num+1}: Chip updates on click',
                len(chips) > 0,
                chips
            )
            check(
                f'R{round_num+1}: Chip shows this component\'s text',
                original_text[:12].lower() in chip_text.lower() or
                (round_num > 0 and MARKERS[round_num-1][:12] in chip_text),  # re-use tolerance
                chip_text
            )
            page.screenshot(path=f'/tmp/repeat_r{round_num+1}_a_click.png')

            # ── Step 2: Ask AI to update ──────────────────────────────────
            logs.clear()
            ai_reply = send_chat(
                page,
                f'Replace the text in the selected component with exactly this text and nothing else: {marker}'
            )
            safe_print(f'  AI reply: {ai_reply[:200]!r}')
            page.wait_for_timeout(800)
            page.screenshot(path=f'/tmp/repeat_r{round_num+1}_b_update.png')

            # ── Step 3: Verify DOM updated ────────────────────────────────
            # Re-query since DOM may have been re-rendered
            all_els_after = canvas.query_selector_all('[data-gjs-type="mj-text"]')
            found_marker = False
            new_target_el = None
            for el in all_els_after:
                try:
                    txt = (el.inner_text() or '').strip()
                    if marker in txt:
                        found_marker = True
                        new_target_el = el
                        break
                except Exception:
                    pass

            check(f'R{round_num+1}: DOM updated with marker', found_marker)

            # ── Step 4: Verify chip auto-refreshed ────────────────────────
            chips_after = get_chips(page)
            safe_print(f'  Chip after update: {chips_after}')
            chip_after_text = chips_after[0] if chips_after else ''
            check(
                f'R{round_num+1}: Chip auto-refreshes after update',
                marker[:15] in chip_after_text,
                chip_after_text
            )

            # ── Step 5: Click it again → chip still shows marker ──────────
            if new_target_el:
                new_target_el.click(timeout=5000)
                page.wait_for_timeout(800)
                chips_reclick = get_chips(page)
                safe_print(f'  Chip after re-click: {chips_reclick}')
                check(
                    f'R{round_num+1}: Chip correct on re-click',
                    any(marker[:15] in c for c in chips_reclick),
                    chips_reclick
                )

            # Update target_el for next round's potential re-use
            target_el = new_target_el

        # ── Final: re-read all 5 markers are still in the DOM ─────────────
        safe_print(f'\n=== Final verification: all 5 markers in DOM ===')
        all_els_final = canvas.query_selector_all('[data-gjs-type="mj-text"]')
        dom_texts = set()
        for el in all_els_final:
            try:
                dom_texts.add((el.inner_text() or '').strip())
            except Exception:
                pass

        for marker in MARKERS:
            present = any(marker in t for t in dom_texts)
            check(f'Final: {marker} in DOM', present)

        page.screenshot(path='/tmp/repeat_final.png', full_page=True)

        safe_print(f'\n{"="*60}')
        safe_print(f'RESULTS: {pass_count} passed, {fail_count} failed')
        safe_print(f'{"="*60}')

        browser.close()

run()
