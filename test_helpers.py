"""
Shared helpers for all OdradekAI UAT tests.
"""
import sys
import re
sys.stdout.reconfigure(encoding='utf-8')

BASE  = 'http://localhost:8080'
EMAIL = 'stoker_jan@hotmail.com'
PASS  = 'Easter3-Upwind9-Tinwork6-Superior9'


def safe_print(msg):
    try:
        print(msg)
    except UnicodeEncodeError:
        print(msg.encode('ascii', 'replace').decode('ascii'))


def login(page):
    """Log in to Mautic. Asserts that login succeeds."""
    page.goto(f'{BASE}/s/login')
    page.wait_for_load_state('networkidle')
    page.fill('#username', EMAIL)
    page.fill('#password', PASS)
    page.click('button[type=submit]')
    page.wait_for_load_state('networkidle')
    assert '/login' not in page.url, f'Login failed, still at {page.url}'


def goto_ai(page):
    """Navigate to the Odradek AI page and wait for the chat input."""
    page.goto(f'{BASE}/s/odradek/ai')
    page.wait_for_load_state('networkidle')
    page.wait_for_selector('#odradek-input', timeout=10000)


def send_chat(page, text, wait_ms=45000):
    """
    Fill the chat input, click send, wait for the response to finish,
    and return the last AI reply text.

    Uses CSS-selector polling instead of wait_for_function to avoid
    Mautic's CSP blocking eval() (CSP lacks 'unsafe-eval').
    """
    page.fill('#odradek-input', text)
    page.click('#odradek-send')
    # Wait for button to go disabled (confirms request started)
    try:
        page.wait_for_selector('#odradek-send[disabled]', timeout=3000)
    except Exception:
        pass  # might be instant or already done
    # Wait for button to be re-enabled (response fully delivered)
    page.wait_for_selector('#odradek-send:not([disabled])', timeout=wait_ms)
    page.wait_for_timeout(500)
    return get_last_ai_reply(page)


def get_last_ai_reply(page):
    """Return the textContent of the last AI response message body."""
    msgs = page.eval_on_selector_all(
        '.exchange-main .msg-body',
        "els => els.map(e => e.textContent)"
    )
    return msgs[-1] if msgs else ''


def get_activity_names(page):
    """
    Return the list of activity (tool call) names from the last exchange card.
    Each entry is the text of a .activity-name element.
    """
    return page.eval_on_selector_all(
        '.exchange-card:last-child .activity-name',
        "els => els.map(e => e.textContent.trim())"
    )


def get_chips(page):
    """Return the label texts of all current context chips."""
    return page.eval_on_selector_all(
        '#odradek-context-chips .context-chip span:first-child',
        "els => els.map(e => e.textContent)"
    )


def extract_id(text, min_digits=2, max_digits=7):
    """
    Extract the first plausible entity ID (integer) from AI reply text.
    Prefers explicit #ID patterns (e.g. 'Contact #83'), falls back to
    standalone numbers. Returns the ID as a string, or None if not found.
    """
    # Prefer explicit #ID marker — most reliable
    for m in re.findall(r'#(\d+)\b', text):
        if min_digits <= len(m) <= max_digits:
            return m
    # Fall back: first standalone number with sufficient digits
    matches = re.findall(r'\b(\d{' + str(min_digits) + r',' + str(max_digits) + r'})\b', text)
    return matches[0] if matches else None


def has_activity(activities, tool_name):
    """Check if any activity in the list contains the given tool name."""
    return any(tool_name in a for a in activities)
