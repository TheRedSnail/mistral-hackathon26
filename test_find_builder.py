from playwright.sync_api import sync_playwright

BASE  = 'http://localhost:8080'
EMAIL = 'stoker_jan@hotmail.com'
PASS  = 'Easter3-Upwind9-Tinwork6-Superior9'

JS = """
els => els.map(e => ({
    tag: e.tagName,
    text: e.textContent.trim().slice(0,50),
    id: e.id,
    cls: e.className.slice(0,60),
    href: e.getAttribute('href') || '',
    visible: e.offsetParent !== null
})).filter(b => b.visible && b.text)
"""

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    ctx = browser.new_context(viewport={'width': 1600, 'height': 900})
    page = ctx.new_page()

    page.goto(f'{BASE}/s/login')
    page.wait_for_load_state('networkidle')
    page.fill('#username', EMAIL)
    page.fill('#password', PASS)
    page.click('button[type=submit]')
    page.wait_for_load_state('networkidle')

    page.goto(f'{BASE}/s/odradek/ai')
    page.wait_for_load_state('networkidle')
    page.wait_for_timeout(1500)

    page.evaluate("document.getElementById('odradek-mautic-frame').src = 'http://localhost:8080/s/emails/edit/75'")
    page.wait_for_timeout(3000)

    email_frame = next(f for f in page.frames if 'emails/edit/75' in f.url)
    email_frame.wait_for_load_state('networkidle')

    btns = email_frame.eval_on_selector_all('a, button', JS)
    print('Visible links/buttons:')
    for b in btns:
        print(' ', b)

    browser.close()
