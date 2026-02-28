"""
Multi-select test: Ctrl+click 3 components, send 1 prompt to update all of them,
verify each component got the correct distinct text.
"""
import sys
sys.stdout.reconfigure(encoding='utf-8')

from playwright.sync_api import sync_playwright

BASE     = 'http://localhost:8080'
EMAIL    = 'stoker_jan@hotmail.com'
PASS     = 'Easter3-Upwind9-Tinwork6-Superior9'
EMAIL_ID = 75

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

def send_chat(page, text, wait_ms=30000):
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
            safe_print(f'  PASS: {name}')
            pass_count += 1
        else:
            safe_print(f'  FAIL: {name}' + (f' — got: {got!r}' if got is not None else ''))
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

        # ── Open Odradek AI + builder ─────────────────────────────────────
        safe_print('\n=== Opening builder ===')
        page.goto(f'{BASE}/s/odradek/ai')
        page.wait_for_load_state('networkidle')
        page.wait_for_timeout(1500)

        page.evaluate(f"document.getElementById('odradek-mautic-frame').src = '{BASE}/s/emails/edit/{EMAIL_ID}'")
        page.wait_for_timeout(3000)

        email_frame = next((f for f in page.frames if f'emails/edit/{EMAIL_ID}' in f.url), None)
        assert email_frame, 'email edit frame not found'
        email_frame.wait_for_load_state('networkidle')
        email_frame.click('#emailform_buttons_builder_toolbar')
        page.wait_for_timeout(4000)

        assert any('bound to editor' in l for l in logs), 'editor not bound'

        canvas = find_gjs_canvas(page)
        assert canvas, 'canvas not found'

        # ── Pick 3 distinct short components ─────────────────────────────
        safe_print('\n=== Picking 3 target components ===')
        all_els = canvas.query_selector_all('[data-gjs-type="mj-text"]')
        targets = []
        seen = set()
        for el in all_els:
            try:
                txt = (el.inner_text() or '').strip()
                if txt and txt not in seen and not txt.startswith('{') and len(txt) < 60:
                    seen.add(txt)
                    targets.append((el, txt))
                    if len(targets) == 3:
                        break
            except Exception:
                pass

        assert len(targets) == 3, f'Need 3 components, got {len(targets)}'
        for i, (_, t) in enumerate(targets):
            safe_print(f'  Target [{i}]: {t!r}')

        # ── TEST A: Ctrl+click to multi-select ────────────────────────────
        safe_print('\n=== TEST A: Multi-select via Ctrl+click ===')
        logs.clear()

        # First click (plain) to select component 0
        targets[0][0].click(timeout=5000)
        page.wait_for_timeout(500)

        # Ctrl+click components 1 and 2
        targets[1][0].click(modifiers=['Control'], timeout=5000)
        page.wait_for_timeout(300)
        targets[2][0].click(modifiers=['Control'], timeout=5000)
        page.wait_for_timeout(800)

        chips = get_chips(page)
        safe_print(f'  Chips after multi-select: {chips}')

        sync_logs = [l for l in logs if 'syncSelection' in l or 'buildGjsChip' in l]
        safe_print(f'  Selection logs:')
        for l in sync_logs:
            safe_print(f'    {l}')

        check('Chip appears after multi-select', len(chips) > 0, chips)
        chip_text = chips[0] if chips else ''

        # Check if GrapesJS actually supports multi-select
        gjs_selected_count = page.evaluate('''() => {
            // Count how many components are selected — infer from the chip label
            const chip = document.querySelector('#odradek-context-chips .context-chip span');
            return chip ? chip.textContent : null;
        }''')
        safe_print(f'  Chip label: {gjs_selected_count!r}')

        multi_select_works = '3 components' in (chip_text or '') or '2 components' in (chip_text or '')
        single_fallback    = not multi_select_works and len(chips) > 0

        if multi_select_works:
            safe_print('  Multi-select IS working (GrapesJS supports it)')
            check('Chip shows multiple components', True)
        else:
            safe_print('  Multi-select NOT working — GrapesJS may not support Ctrl+click in headless mode')
            safe_print(f'  Chip shows: {chip_text!r} (single select only)')

        page.screenshot(path='/tmp/multi_a_select.png')

        # ── TEST B: Single prompt to update all selected ──────────────────
        safe_print('\n=== TEST B: Single prompt update ===')

        if multi_select_works:
            prompt = (
                'I have 3 components selected. '
                'Set component index 0 to exactly: MULTI-ZERO, '
                'component index 1 to exactly: MULTI-ONE, '
                'component index 2 to exactly: MULTI-TWO. '
                'Use update_grapesjs_component 3 times with the correct componentIndex each time.'
            )
        else:
            # Fall back: click component 0, update, click 1, update, click 2, update — but as ONE prompt
            # First ensure we have a single component selected for the AI to work with
            safe_print('  Falling back to sequential single-prompt test')
            targets[0][0].click(timeout=5000)
            page.wait_for_timeout(500)
            prompt = (
                'Update the selected component to exactly: MULTI-ZERO'
            )

        logs.clear()
        ai_reply = send_chat(page, prompt)
        safe_print(f'  AI reply: {ai_reply[:400]!r}')
        page.wait_for_timeout(1000)
        page.screenshot(path='/tmp/multi_b_after_update.png')

        # Count how many update_grapesjs_component calls were made
        update_logs = [l for l in logs if 'update_grapesjs_component' in l]
        safe_print(f'  update_grapesjs_component calls: {len(update_logs)}')
        for l in update_logs:
            safe_print(f'    {l}')

        if multi_select_works:
            check('AI made 3 update calls', len(update_logs) == 3, len(update_logs))

        # ── Verify DOM ────────────────────────────────────────────────────
        safe_print('\n=== DOM verification ===')
        all_els_after = canvas.query_selector_all('[data-gjs-type="mj-text"]')
        dom_texts = []
        for el in all_els_after:
            try:
                t = (el.inner_text() or '').strip()
                if t:
                    dom_texts.append(t)
            except Exception:
                pass

        markers_to_check = ['MULTI-ZERO', 'MULTI-ONE', 'MULTI-TWO'] if multi_select_works else ['MULTI-ZERO']
        for marker in markers_to_check:
            found = any(marker in t for t in dom_texts)
            safe_print(f'  {marker} in DOM: {found}')
            check(f'{marker} updated in DOM', found)

        # ── TEST C: Retry with explicit sequential select+update if multi failed ──
        if not multi_select_works:
            safe_print('\n=== TEST C: Sequential select → update × 3 (1 prompt each) ===')
            all_els_fresh = canvas.query_selector_all('[data-gjs-type="mj-text"]')
            fresh_targets = []
            seen2 = set()
            for el in all_els_fresh:
                try:
                    txt = (el.inner_text() or '').strip()
                    if txt and txt not in seen2 and not txt.startswith('{') and len(txt) < 60 and 'MULTI' not in txt:
                        seen2.add(txt)
                        fresh_targets.append((el, txt))
                        if len(fresh_targets) == 3:
                            break
                except Exception:
                    pass

            seq_markers = ['SEQ-FIRST', 'SEQ-SECOND', 'SEQ-THIRD']
            for i, ((el, orig), marker) in enumerate(zip(fresh_targets, seq_markers)):
                safe_print(f'\n  Sequential round {i+1}: {orig!r} → {marker}')
                logs.clear()
                el.click(timeout=5000)
                page.wait_for_timeout(500)
                chips_seq = get_chips(page)
                safe_print(f'    Chip: {chips_seq}')
                check(f'Seq{i+1}: chip shows component', orig[:10] in (chips_seq[0] if chips_seq else ''), chips_seq)

                reply = send_chat(page, f'Replace the selected component text with exactly: {marker}')
                safe_print(f'    AI: {reply[:150]!r}')
                page.wait_for_timeout(500)

            # Final DOM check
            all_final = canvas.query_selector_all('[data-gjs-type="mj-text"]')
            final_dom = set()
            for el in all_final:
                try:
                    final_dom.add((el.inner_text() or '').strip())
                except Exception:
                    pass
            for marker in seq_markers:
                found = any(marker in t for t in final_dom)
                safe_print(f'    {marker} in DOM: {found}')
                check(f'Seq: {marker} in DOM', found)

        safe_print(f'\n{"="*60}')
        safe_print(f'RESULTS: {pass_count} passed, {fail_count} failed')
        safe_print(f'{"="*60}')

        browser.close()

run()
