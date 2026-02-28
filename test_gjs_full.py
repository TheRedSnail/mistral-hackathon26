"""
Full GrapesJS selection test.
Tests:
  A) Click component A → chip shows A's content
  B) Click component B → chip updates to B's content
  C) Ask AI "what is in the selected component" → should report B, not A
  D) Ask AI to update component → verify content syncs back on re-select
"""
import sys
sys.stdout.reconfigure(encoding='utf-8')

from playwright.sync_api import sync_playwright
import time, json

BASE     = 'http://localhost:8080'
EMAIL    = 'stoker_jan@hotmail.com'
PASS     = 'Easter3-Upwind9-Tinwork6-Superior9'
EMAIL_ID = 75

odradek_logs = []

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

def get_chip_data(page):
    """Read the full data of the GJS chip from contextItems state."""
    return page.evaluate("""() => {
        // The state object is inside the IIFE closure — we can't access it directly.
        // Instead read the chip label from the DOM.
        const chips = document.querySelectorAll('#odradek-context-chips .context-chip');
        return Array.from(chips).map(c => ({
            label: c.querySelector('span')?.textContent,
            id: c.querySelector('.context-chip-remove')?.dataset?.id
        }));
    }""")

def send_chat(page, text, wait_ms=15000):
    """Type a message and send it, wait for response, return AI text."""
    page.fill('#odradek-input', text)
    page.click('#odradek-send')
    # Wait for busy to clear (send button re-enabled)
    page.wait_for_function("!document.getElementById('odradek-send').disabled", timeout=wait_ms)
    page.wait_for_timeout(500)
    # Get last AI message
    msgs = page.eval_on_selector_all(
        '.msg-ai .msg-body',
        "els => els.map(e => e.textContent)"
    )
    return msgs[-1] if msgs else ''

def find_gjs_canvas(page):
    """Find the GrapesJS canvas iframe (about:blank with most content)."""
    best = None
    best_len = 0
    for f in page.frames:
        if f.url in ('about:blank', ''):
            try:
                content = f.inner_html('body')
                if content and len(content) > best_len:
                    best_len = len(content)
                    best = f
            except Exception:
                pass
    return best, best_len

def get_gjs_text_components(canvas_frame):
    """Get all text-type components from the GrapesJS canvas."""
    all_comps = canvas_frame.eval_on_selector_all(
        '[data-gjs-type]',
        "els => els.map(e => ({type: e.getAttribute('data-gjs-type'), text: (e.innerText||'').trim().slice(0,100)}))"
    )
    return [c for c in all_comps if 'text' in (c['type'] or '').lower() and c['text']]

def run():
    PASS_COUNT = 0
    FAIL_COUNT = 0

    def check(name, condition, got=None):
        nonlocal PASS_COUNT, FAIL_COUNT
        if condition:
            safe_print(f'  PASS: {name}')
            PASS_COUNT += 1
        else:
            safe_print(f'  FAIL: {name}' + (f' (got: {got!r})' if got is not None else ''))
            FAIL_COUNT += 1

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        ctx = browser.new_context(viewport={'width': 1600, 'height': 900})
        page = ctx.new_page()

        page.on('console', lambda m: (
            odradek_logs.append(f'[{m.type}] {m.text}'),
            safe_print(f'  [CON] {m.text}') if 'OdradekGJS' in m.text else None
        ))

        # ── Login ─────────────────────────────────────────────────────────
        safe_print('\n=== Login ===')
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
        safe_print('\n=== Open email builder ===')
        page.evaluate(f"document.getElementById('odradek-mautic-frame').src = '{BASE}/s/emails/edit/{EMAIL_ID}'")
        page.wait_for_timeout(3000)

        email_frame = next((f for f in page.frames if f'emails/edit/{EMAIL_ID}' in f.url), None)
        assert email_frame, 'email edit frame not found'
        email_frame.wait_for_load_state('networkidle')

        odradek_logs.clear()
        email_frame.click('#emailform_buttons_builder_toolbar')
        page.wait_for_timeout(4000)

        # Verify binding
        bound = any('bound to editor' in l for l in odradek_logs)
        check('GrapesJS editor bound', bound)

        # ── Get canvas and components ─────────────────────────────────────
        canvas, canvas_len = find_gjs_canvas(page)
        check('GrapesJS canvas found', canvas is not None, canvas_len)

        comps = get_gjs_text_components(canvas)
        safe_print(f'  Text components found: {len(comps)}')
        check('Has >=2 text components', len(comps) >= 2)

        if len(comps) < 2:
            safe_print('Not enough components to test — aborting')
            browser.close()
            return

        comp_a_type = comps[0]['type']
        comp_a_text = comps[0]['text']
        comp_b_text = comps[1]['text']
        safe_print(f'  Component A: type={comp_a_type!r} text={comp_a_text!r}')
        safe_print(f'  Component B: type={comps[1]["type"]!r} text={comp_b_text!r}')

        # ── TEST 1: Click A → chip shows A ────────────────────────────────
        safe_print('\n=== TEST 1: Click component A ===')
        odradek_logs.clear()
        all_comps_els = canvas.query_selector_all(f'[data-gjs-type]')
        # Find first text comp
        comp_a_el = None
        for el in all_comps_els:
            if el.get_attribute('data-gjs-type') == comp_a_type:
                inner = (el.inner_text() or '').strip()
                if inner == comp_a_text:
                    comp_a_el = el
                    break
        if comp_a_el:
            comp_a_el.click()
        else:
            canvas.click(f'[data-gjs-type="{comp_a_type}"]')
        page.wait_for_timeout(800)

        chips_a = get_chips(page)
        safe_print(f'  Chips after click A: {chips_a}')
        check('Chip appears after click A', len(chips_a) > 0, chips_a)
        check('Chip A contains A text', any(comp_a_text[:15] in c for c in chips_a), chips_a)
        page.screenshot(path='/tmp/test1_click_a.png')

        # ── TEST 2: Click B → chip updates to B ──────────────────────────
        safe_print('\n=== TEST 2: Click component B ===')
        odradek_logs.clear()
        comp_b_type = comps[1]['type']
        comp_b_el = None
        # Find second distinct text comp
        found_count = 0
        for el in all_comps_els:
            if el.get_attribute('data-gjs-type') in ('mj-text', 'text'):
                inner = (el.inner_text() or '').strip()
                if inner and inner != comp_a_text:
                    comp_b_el = el
                    comp_b_text = inner[:100]
                    break

        if comp_b_el:
            comp_b_el.click()
            page.wait_for_timeout(800)
            chips_b = get_chips(page)
            safe_print(f'  Chips after click B: {chips_b}')
            safe_print(f'  Expected B text: {comp_b_text[:30]!r}')
            check('Chip updates after click B', any(comp_b_text[:15] in c for c in chips_b) if comp_b_text else True, chips_b)
            check('Chip B differs from chip A', chips_b != chips_a, {'a': chips_a, 'b': chips_b})
            page.screenshot(path='/tmp/test2_click_b.png')
        else:
            safe_print('  Could not find a distinct component B — skipping test 2')

        # ── TEST 3: AI reads correct component ───────────────────────────
        safe_print('\n=== TEST 3: Ask AI what is selected ===')
        odradek_logs.clear()
        # Make sure a component is selected (click comp B again)
        if comp_b_el:
            comp_b_el.click()
            page.wait_for_timeout(500)

        chips_before_send = get_chips(page)
        safe_print(f'  Chips before send: {chips_before_send}')

        # Send chat message
        ai_reply = send_chat(page, 'What is the text content of the currently selected component? Just quote it exactly.')
        safe_print(f'  AI reply: {ai_reply[:300]!r}')

        # Check if AI mentioned the correct text
        current_comp_text = comp_b_text if comp_b_el else comp_a_text
        check('AI reply not empty', len(ai_reply) > 5, ai_reply)
        check('AI reply mentions correct component text',
              current_comp_text[:15].lower() in ai_reply.lower() if current_comp_text else True,
              {'expected': current_comp_text[:30], 'got': ai_reply[:100]})
        page.screenshot(path='/tmp/test3_ai_read.png')

        # Capture any context logs
        ctx_logs = [l for l in odradek_logs if 'buildContext' in l or 'captureGjs' in l]
        safe_print(f'  Context logs:')
        for l in ctx_logs:
            safe_print(f'    {l}')

        # ── TEST 4: Click A again after AI response → chip shows A ───────
        safe_print('\n=== TEST 4: Switch back to A after AI chat ===')
        odradek_logs.clear()
        if comp_a_el:
            comp_a_el.click()
        else:
            canvas.click(f'[data-gjs-type="{comp_a_type}"]')
        page.wait_for_timeout(800)
        chips_switch = get_chips(page)
        safe_print(f'  Chips after switching to A: {chips_switch}')
        check('Chip switches back to A', any(comp_a_text[:15] in c for c in chips_switch), chips_switch)
        page.screenshot(path='/tmp/test4_switch_a.png')

        # ── TEST 5: Ask AI to update selected component ───────────────────
        safe_print('\n=== TEST 5: Ask AI to update component A ===')
        # Click A to make sure it's selected
        if comp_a_el:
            comp_a_el.click()
        page.wait_for_timeout(500)
        chips_pre_update = get_chips(page)
        safe_print(f'  Chips before update: {chips_pre_update}')

        update_reply = send_chat(page, 'Replace the text in the selected component with exactly: TEST-UPDATED-CONTENT')
        safe_print(f'  AI reply: {update_reply[:300]!r}')

        page.wait_for_timeout(1500)
        page.screenshot(path='/tmp/test5_after_update.png')

        # After components(html) GrapesJS re-renders DOM — must re-query, old handle is stale.
        safe_print('  Re-querying component A by selector (stale handle after DOM re-render)...')
        odradek_logs.clear()

        # Verify DOM content directly — find mj-text elements and check their text
        all_text_els = canvas.query_selector_all('[data-gjs-type="mj-text"]')
        safe_print(f'  Found {len(all_text_els)} mj-text elements after update')
        found_updated = False
        for el in all_text_els:
            try:
                txt = (el.inner_text() or '').strip()
                safe_print(f'    mj-text text: {txt!r}')
                if 'TEST-UPDATED-CONTENT' in txt:
                    found_updated = True
                    # Click this freshly-queried element
                    el.click(timeout=5000)
                    page.wait_for_timeout(1000)

                    # Diagnose: what does GrapesJS think is selected inside the iframe?
                    debug = email_frame.evaluate('''() => {
                        const gjs = window.grapesjs;
                        if (!gjs || !gjs.editors || !gjs.editors.length) return {error: "no gjs"};
                        const ed = gjs.editors[gjs.editors.length - 1];
                        const all = ed.getSelectedAll ? ed.getSelectedAll() : [];
                        return {
                            count: all.length,
                            selected: all.map(function(c) {
                                return {
                                    type: c.get("type"),
                                    contentAttr: (c.get("content") || "").slice(0, 80),
                                    viewInnerHTML: (c.view && c.view.el) ? c.view.el.innerHTML.slice(0, 80) : null
                                };
                            })
                        };
                    }''')
                    safe_print(f'    GrapesJS selection after re-click: {debug}')
                    break
            except Exception as e:
                safe_print(f'    (error: {e})')

        check('Component A updated in DOM', found_updated)

        chips_after_update = get_chips(page)
        safe_print(f'  Chips after re-click A: {chips_after_update}')
        check('Chip shows updated content after re-click',
              any('TEST-UPDATED-CONTENT' in c for c in chips_after_update),
              chips_after_update)

        page.screenshot(path='/tmp/test5_recheck.png')

        # ── Summary ───────────────────────────────────────────────────────
        safe_print(f'\n{"="*50}')
        safe_print(f'RESULTS: {PASS_COUNT} passed, {FAIL_COUNT} failed')
        safe_print(f'{"="*50}')

        browser.close()

run()
