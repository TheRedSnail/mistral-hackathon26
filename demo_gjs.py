"""
Visual demo: page-context chip + GrapesJS selection chip.
Runs with a visible browser window and slow_mo so you can watch it happen.
"""
import sys
sys.stdout.reconfigure(encoding='utf-8')

from playwright.sync_api import sync_playwright
import time

BASE     = 'http://localhost:8080'
EMAIL    = 'stoker_jan@hotmail.com'
PASS     = 'Easter3-Upwind9-Tinwork6-Superior9'
EMAIL_ID = 75

def banner(msg):
    print(f'\n{"─"*60}')
    print(f'  {msg}')
    print(f'{"─"*60}')

def get_chips(page):
    return page.eval_on_selector_all(
        '#odradek-context-chips .context-chip span:first-child',
        "els => els.map(e => e.textContent)"
    )

def pause(page, seconds, reason=''):
    if reason:
        print(f'  ⏸  {reason}')
    page.wait_for_timeout(seconds * 1000)

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
    return best, best_len

with sync_playwright() as p:
    browser = p.chromium.launch(headless=False, slow_mo=400)
    ctx     = browser.new_context(viewport={'width': 1600, 'height': 900})
    page    = ctx.new_page()

    # ── 1. Login ──────────────────────────────────────────────────────────────
    banner('STEP 1 — Login to Mautic')
    page.goto(f'{BASE}/s/login')
    page.wait_for_load_state('networkidle')
    page.fill('#username', EMAIL)
    page.fill('#password', PASS)
    page.click('button[type=submit]')
    page.wait_for_load_state('networkidle')
    print('  Logged in ✓')

    # ── 2. Open Odradek AI ────────────────────────────────────────────────────
    banner('STEP 2 — Open Odradek AI page')
    page.goto(f'{BASE}/s/odradek/ai')
    page.wait_for_load_state('networkidle')
    pause(page, 2, 'Odradek AI loaded — chips should be empty')
    chips = get_chips(page)
    print(f'  Chips: {chips}')

    # ── 3. Navigate iframe → Contacts (first page nav) ────────────────────────
    banner('STEP 3 — Navigate iframe to Contacts list')
    page.evaluate(f"document.getElementById('odradek-mautic-frame').src = '{BASE}/s/contacts'")
    pause(page, 3, 'Waiting for Contacts page to load in iframe...')
    chips = get_chips(page)
    print(f'  Page chip auto-appeared: {chips}')

    # ── 4. Navigate iframe → Emails (second page nav — chip should UPDATE) ────
    banner('STEP 4 — Navigate iframe to Emails list (chip must update, not stack)')
    page.evaluate(f"document.getElementById('odradek-mautic-frame').src = '{BASE}/s/emails'")
    pause(page, 3, 'Waiting for Emails page to load...')
    chips = get_chips(page)
    print(f'  Chip after nav to Emails: {chips}')
    assert len([c for c in chips if 'page' in c.lower() or 'email' in c.lower() or 'Email' in c]) > 0 or len(chips) > 0, 'Should have a chip'
    page_chip_count = sum(1 for c in chips if not c.startswith('⬡'))
    print(f'  Page chips visible: {page_chip_count} (should be 1, not stacking)')

    # ── 5. Navigate iframe → Email edit page ──────────────────────────────────
    banner('STEP 5 — Open email edit page in iframe')
    page.evaluate(f"document.getElementById('odradek-mautic-frame').src = '{BASE}/s/emails/edit/{EMAIL_ID}'")
    pause(page, 3, 'Waiting for email edit page to load...')
    chips = get_chips(page)
    print(f'  Chip after nav to email edit: {chips}')

    # ── 6. Click the Builder button ───────────────────────────────────────────
    banner('STEP 6 — Click the Builder button to open GrapesJS')
    email_frame = next(
        (f for f in page.frames if f'emails/edit/{EMAIL_ID}' in f.url), None
    )
    if not email_frame:
        print('  ⚠  email edit frame not found — trying iframe src approach')
        email_frame = page.frame(url=f'{BASE}/s/emails/edit/{EMAIL_ID}')

    if email_frame:
        email_frame.click('#emailform_buttons_builder_toolbar')
        pause(page, 4, 'Builder opening... (waiting for GrapesJS to init)')
    else:
        print('  ✗ Could not find email edit frame')

    # ── 7. Find GrapesJS canvas and click first text component ────────────────
    banner('STEP 7 — Click first mj-text component in GrapesJS canvas')
    canvas, canvas_len = find_gjs_canvas(page)
    print(f'  Canvas found: {canvas is not None}  (content length: {canvas_len})')

    comp_texts = []
    if canvas:
        comps = canvas.query_selector_all('[data-gjs-type="mj-text"]')
        print(f'  mj-text components found: {len(comps)}')

        # Click first non-empty one
        clicked = None
        for c in comps:
            try:
                txt = (c.inner_text() or '').strip()
                if txt:
                    comp_texts.append(txt[:60])
                    if clicked is None:
                        print(f'  → Clicking component with text: {txt[:50]!r}')
                        c.click()
                        clicked = txt
            except Exception:
                pass

        pause(page, 1, 'Waiting for chip to appear...')
        chips = get_chips(page)
        print(f'  Chips after click: {chips}')

        gjs_chips = [c for c in chips if c.startswith('⬡')]
        if gjs_chips:
            print(f'  ✓ GJS chip appeared: {gjs_chips[0]}')
        else:
            print(f'  ✗ No GJS chip — chips were: {chips}')

        # ── 8. Click a different component ────────────────────────────────────
        banner('STEP 8 — Click a DIFFERENT component (chip must update)')
        for c in comps:
            try:
                txt = (c.inner_text() or '').strip()
                if txt and txt != clicked:
                    print(f'  → Clicking component with text: {txt[:50]!r}')
                    c.click()
                    pause(page, 1, 'Waiting for chip to update...')
                    chips2 = get_chips(page)
                    print(f'  Chips after second click: {chips2}')
                    gjs2 = [ch for ch in chips2 if ch.startswith('⬡')]
                    if gjs2:
                        print(f'  ✓ Chip updated to: {gjs2[0]}')
                    break
            except Exception:
                pass
    else:
        print('  ✗ Could not find GrapesJS canvas')

    # ── 9. Navigate away — GJS chip should clear, page chip should update ─────
    banner('STEP 9 — Navigate away — GJS chip clears, page chip updates')
    page.evaluate(f"document.getElementById('odradek-mautic-frame').src = '{BASE}/s/dashboard'")
    pause(page, 3, 'Navigating to dashboard...')
    chips_final = get_chips(page)
    print(f'  Final chips: {chips_final}')
    gjs_chips_final = [c for c in chips_final if c.startswith('⬡')]
    page_chips_final = [c for c in chips_final if not c.startswith('⬡')]
    print(f'  GJS chips (should be 0): {len(gjs_chips_final)}')
    print(f'  Page chips (should be 1): {len(page_chips_final)}')

    banner('DEMO COMPLETE — keeping browser open for 10 s')
    pause(page, 10, 'Review the browser window, then it will close...')
    browser.close()
