"""
UAT — Emails (7 tests)
Tests: list, themes, component retrieval, full creation workflow,
       GrapesJS builder text-block reading.
"""
import sys
sys.stdout.reconfigure(encoding='utf-8')

from playwright.sync_api import sync_playwright
from test_helpers import (
    safe_print, login, goto_ai, send_chat, get_chips,
    get_activity_names, has_activity, extract_id, BASE
)

# Fallback email ID for component test (from existing test fixtures)
FALLBACK_EMAIL_ID = 75


def run():
    pass_count = 0
    fail_count = 0
    email_id_for_components = None

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

        # TEST 1 — list_emails activity fires
        safe_print('\n--- TEST 1: list_emails ---')
        reply = send_chat(page, 'List my emails', wait_ms=30000)
        activities = get_activity_names(page)
        safe_print(f'  Activities: {activities}')
        safe_print(f'  Reply (100): {reply[:100]!r}')
        check('list_emails activity fires', has_activity(activities, 'list_emails'), activities)

        # Try to extract an email ID for use in TEST 5
        email_id_for_components = extract_id(reply)
        if not email_id_for_components:
            email_id_for_components = str(FALLBACK_EMAIL_ID)
        safe_print(f'  Email ID for component test: {email_id_for_components}')
        page.screenshot(path='/tmp/uat_emails_01.png')

        # TEST 2 — Response contains email subjects or is non-trivial
        safe_print('\n--- TEST 2: Reply has email content ---')
        check('Reply has email content (length > 20)', len(reply.strip()) > 20, len(reply))

        # TEST 3 — list_email_themes works
        safe_print('\n--- TEST 3: list_email_themes ---')
        reply = send_chat(page, 'List all available email themes', wait_ms=30000)
        activities = get_activity_names(page)
        safe_print(f'  Activities: {activities}')
        safe_print(f'  Reply (100): {reply[:100]!r}')
        check('list_email_themes activity fires', has_activity(activities, 'list_email_themes'), activities)
        page.screenshot(path='/tmp/uat_emails_03.png')

        # TEST 4 — At least one theme mentioned
        safe_print('\n--- TEST 4: Theme in reply ---')
        has_theme = any(w in reply.lower() for w in ['theme', 'paprika', 'vibrant', 'template'])
        safe_print(f'  Theme mentioned: {has_theme}')
        check('Reply mentions a theme name', has_theme, reply[:150])

        # TEST 5 — get_email_components works
        safe_print('\n--- TEST 5: get_email_components ---')
        reply = send_chat(
            page,
            f'Get the components of email {email_id_for_components}',
            wait_ms=30000
        )
        activities = get_activity_names(page)
        safe_print(f'  Activities: {activities}')
        safe_print(f'  Reply (200): {reply[:200]!r}')
        check(
            'get_email_components activity fires',
            has_activity(activities, 'get_email_components'),
            activities
        )
        check(
            'Reply mentions components/slots',
            any(w in reply.lower() for w in ['component', 'slot', '#', 'index', 'text']),
            reply[:200]
        )
        page.screenshot(path='/tmp/uat_emails_05.png')

        # TEST 6 — Full email creation workflow (3-min timeout for multi-tool)
        safe_print('\n--- TEST 6: Full email creation ---')
        try:
            reply = send_chat(
                page,
                (
                    "Draft a new email with subject 'UAT Email Test' about a summer sale, "
                    "using the first available theme. Fill all content slots."
                ),
                wait_ms=180000
            )
        except Exception as e:
            safe_print(f'  Timeout/error: {e}')
            reply = ''
        activities = get_activity_names(page)
        safe_print(f'  Activities ({len(activities)}): {activities}')
        safe_print(f'  Reply (200): {reply[:200]!r}')
        check(
            'Multiple activities fired (>=3 for full creation)',
            len(activities) >= 3,
            activities
        )
        check(
            'Reply mentions email ID or creation success',
            any(w in reply.lower() for w in ['created', 'email', 'id', '#', 'summer', 'draft']),
            reply[:200]
        )
        page.screenshot(path='/tmp/uat_emails_06.png')

        # TEST 7 — GrapesJS builder: select text block → AI reads correct text
        safe_print('\n--- TEST 7: GrapesJS text-block reading ---')
        # Always use the fixture email (75) which has real text content, not just tokens
        gjs_email_id = str(FALLBACK_EMAIL_ID)
        try:
            # Navigate the iframe to the email edit page
            page.evaluate(
                f"document.getElementById('odradek-mautic-frame').src = "
                f"'{BASE}/s/emails/edit/{gjs_email_id}'"
            )
            page.wait_for_timeout(3000)

            # Wait for the email edit frame to appear
            email_frame = None
            for _ in range(20):
                email_frame = next(
                    (f for f in page.frames if f'emails/edit/{gjs_email_id}' in f.url),
                    None
                )
                if email_frame:
                    break
                page.wait_for_timeout(300)

            if not email_frame:
                check('Email edit frame found', False, 'frame never appeared')
            else:
                email_frame.wait_for_load_state('networkidle')

                # Open the GrapesJS builder
                email_frame.click('#emailform_buttons_builder_toolbar')
                page.wait_for_timeout(5000)   # give the builder time to fully render

                # Find the GrapesJS canvas frame (about:blank with most HTML content)
                canvas = None
                canvas_len = 0
                for _ in range(10):
                    for f in page.frames:
                        if f.url in ('about:blank', ''):
                            try:
                                html_len = len(f.inner_html('body'))
                                if html_len > canvas_len:
                                    canvas_len = html_len
                                    canvas = f
                            except Exception:
                                pass
                    if canvas and canvas_len > 500:
                        break
                    page.wait_for_timeout(500)

                safe_print(f'  GrapesJS canvas found: {canvas is not None} ({canvas_len} chars)')
                check('GrapesJS canvas found', canvas is not None, canvas_len)

                if canvas:
                    # Enumerate all [data-gjs-type] elements and their text
                    all_gjs = canvas.eval_on_selector_all(
                        '[data-gjs-type]',
                        "els => els.map(e => ({type: e.getAttribute('data-gjs-type'), "
                        "text: (e.innerText||'').trim().slice(0,300)}))"
                    )
                    safe_print(f'  Total GrapesJS components: {len(all_gjs)}')

                    # Pick a text-bearing component — prefer real content over Mautic tokens
                    text_comps = [
                        c for c in all_gjs
                        if 'text' in (c['type'] or '').lower() and c['text'].strip()
                    ]
                    # Prefer components whose text isn't a bare Mautic token
                    real_comps = [
                        c for c in text_comps
                        if not c['text'].strip().startswith('{')
                    ]
                    safe_print(f'  Text components: {len(text_comps)} total, {len(real_comps)} with real content')
                    for i, c in enumerate((real_comps or text_comps)[:3]):
                        safe_print(f'    [{i}] type={c["type"]!r} text={c["text"][:60]!r}')

                    check('Builder has text components', len(text_comps) > 0, len(all_gjs))

                    if text_comps:
                        target = (real_comps or text_comps)[0]
                        target_type = target['type']
                        target_text = target['text']   # actual DOM text (ground truth)
                        safe_print(f'  Target component: type={target_type!r}')
                        safe_print(f'  Target text (DOM ground truth): {target_text[:100]!r}')

                        # Click the element in the canvas
                        clicked = False
                        for el in canvas.query_selector_all(f'[data-gjs-type="{target_type}"]'):
                            try:
                                inner = (el.inner_text() or '').strip()
                                if inner and inner[:50] == target_text[:50]:
                                    el.click()
                                    clicked = True
                                    break
                            except Exception:
                                pass
                        if not clicked:
                            # Fallback: click first element of that type
                            el = canvas.query_selector(f'[data-gjs-type="{target_type}"]')
                            if el:
                                el.click()
                                clicked = True

                        page.wait_for_timeout(1000)
                        safe_print(f'  Clicked component: {clicked}')

                        # Check the context chip was created and shows the right text
                        chips = get_chips(page)
                        safe_print(f'  Context chips after click: {chips}')
                        chip_ok = any(target_text[:15] in c for c in chips) if chips else False
                        check('Chip shows component text after click', chip_ok,
                              {'chips': chips, 'expected': target_text[:30]})

                        # Diagnostic: read chip label vs DOM text
                        chip_label = chips[0] if chips else ''
                        safe_print(f'  Chip label: {chip_label!r}')
                        safe_print(f'  DOM text:   {target_text[:60]!r}')

                        # Ask the AI what the selected component says (read-only — no tool call expected)
                        ai_reply = send_chat(
                            page,
                            'What is the text in the currently selected component? '
                            'Quote it exactly without changing anything.',
                            wait_ms=60000
                        )
                        safe_print(f'  AI reply: {ai_reply[:300]!r}')
                        page.screenshot(path='/tmp/uat_emails_07.png')

                        check('AI reply is non-empty', len(ai_reply.strip()) > 10, ai_reply[:50])
                        # The AI should quote at least the first 20 chars of the component text
                        snippet = target_text[:20].lower().strip()
                        ai_has_text = snippet in ai_reply.lower() if snippet else False
                        check(
                            f'AI quotes correct text ({snippet!r})',
                            ai_has_text,
                            {'expected_in_reply': snippet, 'ai_reply': ai_reply[:150]}
                        )

        except Exception as exc:
            import traceback
            safe_print(f'  ERROR in TEST 7: {exc}')
            safe_print(traceback.format_exc())
            check('TEST 7 completed without error', False, str(exc))

        # ── Summary ────────────────────────────────────────────────────────
        safe_print(f'\n{"="*50}')
        safe_print(f'Emails (7 tests): {pass_count} passed, {fail_count} failed')
        safe_print(f'{"="*50}')

        browser.close()

    return pass_count, fail_count


if __name__ == '__main__':
    passed, failed = run()
    sys.exit(0 if failed == 0 else 1)
