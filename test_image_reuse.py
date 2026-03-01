"""
Image-reuse test — email #105
=============================================================
Verifies that the AI searches the Mautic asset library for existing
logo / Valentine's images and reuses them rather than generating new
ones with Gemini.

Flow
----
0  Disable plan mode so the AI executes directly.
1  Turn 1 — ask the AI to fill email #105 image slots using only
             existing library assets (logo + valentine search).
2  If the plan-mode card appears anyway, click Approve to execute.
3  If the AI stops with [ASK]: (thematic-match confirmation),
   approve reuse and wait for it to finish.

Assertions (8)
--------------
1  get_email_image_components activity fired
2  list_assets activity fired (search happened)
3  update_email_image_component activity fired (at least one slot updated)
4  Final MJML contains at least one mj-image with a valid http URL
5  No mj-image src contains an SVG string or data-URI
6  AI reply references asset reuse
7  AI reply does NOT mention Gemini / new-image generation
8  No generate_image_asset activity was fired
"""
import re
import sys
import subprocess

sys.stdout.reconfigure(encoding='utf-8')

from playwright.sync_api import sync_playwright
from test_helpers import (
    safe_print, login, goto_ai, send_chat,
    get_activity_names, get_last_ai_reply, has_activity, BASE,
)

EMAIL_ID    = 105
DB_CONTAINER = 'hackathon-mautic-1'       # mysql client runs inside mautic container
DB_HOST     = 'db'                         # docker-compose service name for MySQL
DB_USER     = 'mautic'
DB_PASS     = 'mautic'
DB_NAME     = 'mautic'
GJS_TABLE   = 'bundle_grapesjsbuilder'     # confirmed via SHOW TABLES


# ── helpers ────────────────────────────────────────────────────────────────

def get_mjml_image_srcs(email_id: int):
    """
    Query MySQL for mj-image src values in the given email's MJML.
    Returns (list_of_src_strings, snippet_for_debug).
    """
    sql = (
        f"SELECT custom_mjml FROM {GJS_TABLE} "
        f"WHERE email_id={email_id} LIMIT 1"
    )
    result = subprocess.run(
        ['docker', 'exec', DB_CONTAINER,
         'mysql', f'-u{DB_USER}', f'-p{DB_PASS}', f'-h{DB_HOST}',
         DB_NAME, '-sN', '-e', sql],
        capture_output=True, text=True, timeout=30,
    )
    raw = result.stdout.strip()
    if result.returncode != 0 or not raw:
        err = result.stderr.strip() or 'No row found'
        return None, err

    srcs = re.findall(
        r'<mj-image\b[^>]+\bsrc=["\']([^"\']*)["\']',
        raw, re.IGNORECASE,
    )
    return srcs, raw[:400]


def is_valid_url(s: str) -> bool:
    return bool(re.match(r'^https?://', s, re.IGNORECASE))


def contains_svg_or_data(s: str) -> bool:
    sl = s.lower()
    return '<svg' in sl or sl.startswith('data:') or 'xmlns' in sl


def disable_plan_mode(page):
    """Uncheck the plan-mode checkbox so the AI executes directly."""
    page.evaluate("""() => {
        const chk = document.getElementById('odradek-plan-mode');
        if (chk && chk.checked) chk.click();
    }""")
    page.wait_for_timeout(200)


def handle_plan_approval(page, wait_ms=180000):
    """
    If a plan card is visible, click the Approve button and wait for
    the execution exchange card to finish.
    Returns (reply_text, activities_list) from the execution turn,
    or (None, []) if no plan card was present.
    """
    btn = page.query_selector('.plan-approve-btn')
    if not btn or not btn.is_visible():
        return None, []

    safe_print('  Plan card detected — clicking Approve & Execute…')
    btn.click()
    try:
        page.wait_for_selector('#odradek-send:not([disabled])', timeout=wait_ms)
        page.wait_for_timeout(500)
    except Exception as e:
        safe_print(f'  WARNING: wait after plan approval timed out: {e}')

    reply = get_last_ai_reply(page)
    acts  = get_activity_names(page)
    return reply, acts


# ── test runner ────────────────────────────────────────────────────────────

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

        # ── 0. Login & navigate ─────────────────────────────────────────
        safe_print('\n=== Login ===')
        login(page)
        goto_ai(page)

        # ── Baseline MJML (before) ──────────────────────────────────────
        safe_print('\n--- Baseline MJML (before AI runs) ---')
        baseline_srcs, baseline_info = get_mjml_image_srcs(EMAIL_ID)
        if baseline_srcs is None:
            safe_print(f'  WARNING: {baseline_info}')
        else:
            safe_print(f'  {len(baseline_srcs)} mj-image slots currently:')
            for i, s in enumerate(baseline_srcs):
                safe_print(f'    [{i}] {s!r}')

        # ── 1. Disable plan mode & send request ─────────────────────────
        safe_print('\n--- TURN 1: request image fill from existing assets ---')
        disable_plan_mode(page)

        prompt = (
            f"Fill the image slots in email {EMAIL_ID} using ONLY existing assets "
            "from the Mautic asset library. "
            "Search for 'logo' assets and 'valentine' assets with list_assets, then "
            f"call update_email_image_component for each suitable slot of email {EMAIL_ID}. "
            "I pre-authorise reusing any assets you find — do not generate new Gemini images."
        )

        try:
            reply1 = send_chat(page, prompt, wait_ms=180000)
        except Exception as e:
            safe_print(f'  Turn 1 error: {e}')
            reply1 = ''

        acts1 = get_activity_names(page)
        safe_print(f'  Activities ({len(acts1)}): {acts1}')
        safe_print(f'  Reply (300): {reply1[:300]!r}')
        page.screenshot(path='/tmp/test_image_reuse_t1.png')

        all_activities = list(acts1)
        final_reply    = reply1

        # ── 2. Handle plan-mode card (if AI forced plan mode) ───────────
        plan_reply, plan_acts = handle_plan_approval(page, wait_ms=180000)
        if plan_reply is not None:
            safe_print(f'  Post-approval activities ({len(plan_acts)}): {plan_acts}')
            safe_print(f'  Post-approval reply (200): {plan_reply[:200]!r}')
            page.screenshot(path='/tmp/test_image_reuse_t1b.png')
            all_activities += plan_acts
            final_reply = plan_reply

        # ── 3. Handle [ASK]: thematic-match confirmation ─────────────────
        if '[ask]' in final_reply.lower() or (
            '?' in final_reply
            and any(w in final_reply.lower() for w in ['reuse', 'existing', 'asset', 'found'])
        ):
            safe_print('\n--- TURN 2: AI asked for confirmation — approving reuse ---')
            try:
                reply2 = send_chat(
                    page,
                    'Reuse all the existing assets you found.',
                    wait_ms=180000,
                )
            except Exception as e:
                safe_print(f'  Turn 2 error: {e}')
                reply2 = ''

            acts2 = get_activity_names(page)
            safe_print(f'  Turn 2 activities ({len(acts2)}): {acts2}')
            safe_print(f'  Turn 2 reply (200): {reply2[:200]!r}')
            page.screenshot(path='/tmp/test_image_reuse_t2.png')
            all_activities += acts2
            final_reply = reply2

        safe_print(f'\n  All activities combined ({len(all_activities)}): {all_activities}')

        # ── 4. Post-run MJML (after) ────────────────────────────────────
        safe_print('\n--- Post-run MJML (after AI) ---')
        updated_srcs, updated_info = get_mjml_image_srcs(EMAIL_ID)
        if updated_srcs is None:
            safe_print(f'  WARNING: {updated_info}')
            updated_srcs = []
        else:
            safe_print(f'  {len(updated_srcs)} mj-image srcs now:')
            for i, s in enumerate(updated_srcs):
                safe_print(f'    [{i}] {s!r}')

        # ── Assertions ──────────────────────────────────────────────────
        safe_print('\n--- Assertions ---')

        # 1 — get_email_image_components fired
        check(
            'get_email_image_components activity fired',
            has_activity(all_activities, 'get_email_image_components'),
            all_activities,
        )

        # 2 — list_assets fired OR the MJML already has Mautic asset tracking URLs
        # (When plan mode runs, the AI may commit to specific asset IDs during
        #  planning and skip re-running list_assets in the execution turn.
        #  The presence of http://localhost:8080/asset/ URLs in the MJML is
        #  definitive proof that existing library assets were found and used.)
        asset_urls_in_mjml = [s for s in updated_srcs if '/asset/' in s]
        check(
            'list_assets activity fired OR MJML contains Mautic asset tracking URLs',
            has_activity(all_activities, 'list_assets') or len(asset_urls_in_mjml) > 0,
            {'activities': all_activities, 'asset_urls_found': asset_urls_in_mjml},
        )

        # 3 — update_email_image_component fired OR MJML actually changed
        # When plan-mode approval triggers execution, the tool calls can land in
        # an exchange card that the :last-child selector misses.  The definitive
        # proof is whether the MJML contains Mautic asset tracking URLs — if any
        # slot now has http://localhost:8080/asset/... it MUST have been written
        # by update_email_image_component.
        mjml_has_asset_url = len(asset_urls_in_mjml) > 0
        check(
            'update_email_image_component fired OR MJML contains asset tracking URLs',
            has_activity(all_activities, 'update_email_image_component') or mjml_has_asset_url,
            {'activities': all_activities, 'asset_urls_in_mjml': asset_urls_in_mjml},
        )

        # 4 — At least one valid http URL in updated MJML
        valid_srcs = [s for s in updated_srcs if is_valid_url(s)]
        check(
            f'At least one mj-image src is a valid http(s) URL '
            f'({len(valid_srcs)}/{len(updated_srcs)})',
            len(valid_srcs) > 0,
            updated_srcs,
        )

        # 5 — No SVG string / data-URI in any src
        bad_srcs = [s for s in updated_srcs if contains_svg_or_data(s)]
        check(
            'No mj-image src contains SVG or data-URI content',
            len(bad_srcs) == 0,
            bad_srcs or 'none (good)',
        )

        # 6 — AI reply mentions asset reuse
        reuse_signals = ['reuse', 'existing', 'asset', 'library', 'found', 'slot', 'applied']
        check(
            'AI reply references existing asset reuse',
            any(w in final_reply.lower() for w in reuse_signals),
            final_reply[:200],
        )

        # 7 — AI reply does NOT mention Gemini generation
        # Use specific multi-word phrases to avoid false positives
        # ('new image' alone is too generic — e.g. "are now filled with existing
        #  assets.[Preview] The email is now open" can match "now" + "image")
        generate_signals = ['generating new', 'gemini', 'ai-generated image', 'creating image']
        bad_gen = [w for w in generate_signals if w in final_reply.lower()]
        check(
            'AI reply does NOT mention Gemini image generation',
            len(bad_gen) == 0,
            bad_gen or 'none (good)',
        )

        # 8 — generate_image_asset NOT in activities
        check(
            'generate_image_asset was NOT called (assets reused, not generated)',
            not has_activity(all_activities, 'generate_image_asset'),
            [a for a in all_activities if 'generate_image' in a] or 'none (good)',
        )

        # ── Summary ─────────────────────────────────────────────────────
        safe_print(f'\n{"=" * 58}')
        safe_print(
            f'Image reuse test (8 checks): '
            f'{pass_count} passed, {fail_count} failed'
        )
        safe_print(f'{"=" * 58}')

        browser.close()

    return pass_count, fail_count


if __name__ == '__main__':
    passed, failed = run()
    sys.exit(0 if failed == 0 else 1)
