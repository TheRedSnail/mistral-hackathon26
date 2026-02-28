"""
Full GrapesJS selection test:
 1. Login → Odradek AI page
 2. Navigate inner iframe to email edit page
 3. Click "Builder" button to open GrapesJS
 4. Click component A, check chip, click component B, check chip
 5. Send "what is the text in the selected component?" and inspect AI response
 6. Test: ask AI to update selected component
"""
from playwright.sync_api import sync_playwright
import time, json

BASE  = 'http://localhost:8080'
EMAIL = 'stoker_jan@hotmail.com'
PASS  = 'Easter3-Upwind9-Tinwork6-Superior9'
EMAIL_ID = 75

console_logs = []

def run():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True, args=['--disable-web-security'])
        ctx = browser.new_context(viewport={'width': 1600, 'height': 900})
        page = ctx.new_page()

        page.on('console', lambda m: (
            console_logs.append(f'[{m.type}] {m.text}'),
            print(f'  CONSOLE [{m.type}]: {m.text}')
        ))

        # ── 1. Login ──────────────────────────────────────────────────────
        print('\n=== STEP 1: Login ===')
        page.goto(f'{BASE}/s/login')
        page.wait_for_load_state('networkidle')
        page.fill('#username', EMAIL)
        page.fill('#password', PASS)
        page.click('button[type=submit]')
        page.wait_for_load_state('networkidle')
        print('Logged in, URL:', page.url)

        # ── 2. Open Odradek AI ────────────────────────────────────────────
        print('\n=== STEP 2: Odradek AI page ===')
        page.goto(f'{BASE}/s/odradek/ai')
        page.wait_for_load_state('networkidle')
        page.wait_for_timeout(1500)
        page.screenshot(path='/tmp/step2_odradek.png')

        # ── 3. Navigate inner iframe to email editor ──────────────────────
        print('\n=== STEP 3: Navigate iframe to email edit ===')
        page.evaluate(f"document.getElementById('odradek-mautic-frame').src = '{BASE}/s/emails/edit/{EMAIL_ID}'")
        page.wait_for_timeout(3000)
        page.screenshot(path='/tmp/step3_email_edit.png')

        frames = page.frames
        print(f'Frames after navigation: {len(frames)}')
        for i, f in enumerate(frames):
            print(f'  Frame {i}: {f.url}')

        # Find the email edit frame
        email_frame = None
        for f in frames:
            if f'emails/edit/{EMAIL_ID}' in f.url:
                email_frame = f
                break
        if not email_frame:
            print('ERROR: email edit frame not found!')
            page.screenshot(path='/tmp/step3_error.png')
            browser.close()
            return

        print('Found email edit frame:', email_frame.url)

        # ── 4. Find and click the Builder button ──────────────────────────
        print('\n=== STEP 4: Open GrapesJS builder ===')
        email_frame.wait_for_load_state('networkidle')

        # Screenshot the email edit page
        page.screenshot(path='/tmp/step4_before_builder.png')

        # Look for builder button
        builder_btn = email_frame.query_selector('a[data-target="#builder"], button[data-target="#builder"], a.btn[href*="builder"], [data-toggle="builder"]')
        if not builder_btn:
            # Try by text
            builder_btn = email_frame.query_selector('text=Builder')
        if not builder_btn:
            # Try any button/link containing "Builder"
            all_btns = email_frame.eval_on_selector_all(
                'a, button',
                'els => els.map(e => ({text: e.textContent.trim(), id: e.id, cls: e.className, href: e.href || ""}))'
            )
            print('All buttons/links in email frame:')
            for b in all_btns:
                if b['text']:
                    print(f'  {b}')
            # find builder-related
            builder_btns = [b for b in all_btns if 'builder' in b['text'].lower() or 'builder' in b['cls'].lower()]
            print('Builder candidates:', builder_btns)

        page.screenshot(path='/tmp/step4_email_frame.png')

        # Try clicking Builder via text match
        try:
            email_frame.get_by_text('Builder', exact=False).first.click()
            print('Clicked Builder button')
            page.wait_for_timeout(4000)
            page.screenshot(path='/tmp/step4_after_builder_click.png')
        except Exception as e:
            print(f'Could not click Builder: {e}')
            browser.close()
            return

        frames2 = page.frames
        print(f'Frames after builder click: {len(frames2)}')
        for i, f in enumerate(frames2):
            print(f'  Frame {i}: {f.url}')

        page.screenshot(path='/tmp/step4_builder_open.png', full_page=True)

        # ── 5. Look for GrapesJS canvas inside the email frame ────────────
        print('\n=== STEP 5: Find GrapesJS components ===')
        page.wait_for_timeout(2000)

        # GrapesJS renders its canvas in an iframe inside the builder
        all_frames = page.frames
        print(f'All frames: {len(all_frames)}')
        for i, f in enumerate(all_frames):
            print(f'  Frame {i}: {f.url}')

        # Check if OdradekGJS bound to editor
        gjs_logs = [l for l in console_logs if 'OdradekGJS' in l]
        print(f'\nOdradekGJS logs so far ({len(gjs_logs)}):')
        for l in gjs_logs:
            print(' ', l)

        browser.close()
        print('\nDone.')

run()
