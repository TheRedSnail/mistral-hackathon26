"""
Reconnaissance: log into Mautic, navigate to Odradek AI page, then into GrapesJS builder.
"""
from playwright.sync_api import sync_playwright
import time

BASE  = 'http://localhost:8080'
EMAIL = 'stoker_jan@hotmail.com'
PASS  = 'Easter3-Upwind9-Tinwork6-Superior9'

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    ctx = browser.new_context(viewport={'width': 1600, 'height': 900})
    page = ctx.new_page()

    # Capture console from main page
    page.on('console', lambda m: print(f'[console] {m.type}: {m.text}'))

    # ── 1. Login ──────────────────────────────────────────────────────────
    print('=== LOGIN ===')
    page.goto(f'{BASE}/s/login')
    page.wait_for_load_state('networkidle')
    page.fill('#username', EMAIL)
    page.fill('#password', PASS)
    page.click('button[type=submit]')
    page.wait_for_load_state('networkidle')
    print('Post-login URL:', page.url)
    page.screenshot(path='/tmp/01_after_login.png')

    # ── 2. List emails ────────────────────────────────────────────────────
    print('\n=== EMAIL LIST ===')
    page.goto(f'{BASE}/s/emails')
    page.wait_for_load_state('networkidle')
    page.screenshot(path='/tmp/02_emails.png')
    # Get email names and IDs from the list
    rows = page.eval_on_selector_all('table tbody tr', '''rows => rows.map(r => {
        const link = r.querySelector("a[href*='/emails/']");
        return link ? {text: link.textContent.trim(), href: link.href} : null;
    }).filter(Boolean)''')
    print(f'Found {len(rows)} emails:')
    for r in rows[:5]:
        print(f'  {r}')

    # ── 3. Navigate to Odradek AI ─────────────────────────────────────────
    print('\n=== ODRADEK AI PAGE ===')
    page.goto(f'{BASE}/s/odradek/ai')
    page.wait_for_load_state('networkidle')
    page.wait_for_timeout(2000)
    page.screenshot(path='/tmp/03_odradek_ai.png', full_page=True)
    print('URL:', page.url)

    # Inspect frame structure
    frames = page.frames
    print(f'Frames: {len(frames)}')
    for i, f in enumerate(frames):
        print(f'  Frame {i}: {f.url}')

    # Check key elements are present
    chips_el = page.query_selector('#odradek-context-chips')
    print(f'Context chips el: {chips_el is not None}')
    iframe_el = page.query_selector('#odradek-mautic-frame')
    print(f'Mautic iframe el: {iframe_el is not None}')

    browser.close()
    print('\nDone.')
