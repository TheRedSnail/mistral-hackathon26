"""
UAT — Plan Mode UI (questions callout + button behaviour)

Strategy for capturing the fleeting plan card:
  The `plan` SSE event sets .msg-body innerHTML, then the immediately-following
  `done` event calls finalizeCard() which overwrites it.  Both DOM writes may
  land inside one MutationObserver callback.  MutationObserver delivers records
  for BOTH mutations at once, but detached nodes (removed from the live DOM)
  still carry their original child trees in memory.  We therefore scan every
  addedNode across all records and capture the first .plan-questions element
  we find — regardless of whether it has already been removed from the DOM.
"""
import sys
sys.stdout.reconfigure(encoding='utf-8')

from playwright.sync_api import sync_playwright
from test_helpers import safe_print, login, goto_ai

# ---------------------------------------------------------------------------
# MutationObserver JS injected before sending each message.
# Stores captured state in window globals.
# ---------------------------------------------------------------------------
SETUP_OBSERVER_JS = """
() => {
    window.__planCapture = null;

    if (window.__planObserver) {
        window.__planObserver.disconnect();
    }

    window.__planObserver = new MutationObserver((mutations) => {
        for (const mut of mutations) {
            for (const node of mut.addedNodes) {
                if (node.nodeType !== 1) continue;

                // Direct match: the node itself is .plan-questions
                let pq = node.classList.contains('plan-questions') ? node : null;
                // Subtree match: node contains .plan-questions
                if (!pq) pq = node.querySelector && node.querySelector('.plan-questions');

                if (pq) {
                    // Capture plan-questions HTML (node may already be detached,
                    // but its child tree is still intact in memory).
                    const approveBtn = pq.querySelector('.plan-approve-btn');
                    const inputs     = pq.querySelectorAll('.plan-answer');
                    const qLabels    = pq.querySelectorAll('.plan-q');

                    window.__planCapture = {
                        outerHtml:       pq.outerHTML,
                        approveBtnText:  approveBtn ? approveBtn.textContent.trim() : null,
                        inputCount:      inputs.length,
                        questionCount:   qLabels.length,
                        hasLabel:        !!pq.querySelector('.plan-questions-label'),
                        hasQaItem:       !!pq.querySelector('.plan-qa-item'),
                    };

                    // Also check whether a SEPARATE .plan-actions sibling exists
                    // (it should NOT when questions are present)
                    const body = pq.parentElement;
                    window.__planCapture.separatePlanActions =
                        body ? !!body.querySelector('.plan-actions') : null;

                    window.__planObserver.disconnect();
                    return;
                }

                // Fallback: node is .plan-actions (no-questions path)
                if (node.classList.contains('plan-actions')) {
                    const approveBtn = node.querySelector('.plan-approve-btn');
                    window.__planCapture = {
                        outerHtml:            node.outerHTML,
                        approveBtnText:       approveBtn ? approveBtn.textContent.trim() : null,
                        inputCount:           0,
                        questionCount:        0,
                        hasLabel:             false,
                        hasQaItem:            false,
                        separatePlanActions:  true,
                        noQuestionsPath:      true,
                    };
                    window.__planObserver.disconnect();
                    return;
                }
            }
        }
    });

    window.__planObserver.observe(document.getElementById('odradek-messages'), {
        childList: true,
        subtree:   true,
    });
}
"""


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

        safe_print('\n=== Login ===')
        login(page)
        goto_ai(page)

        # ── TEST 1: Inline <style> block is present in <head> ──────────────
        safe_print('\n--- TEST 1: Inline <style> block in <head> ---')
        style_text = page.evaluate("""() =>
            [...document.querySelectorAll('head style')]
                .map(s => s.textContent).join('\\n')
        """)
        has_label_rule   = 'plan-questions-label' in style_text
        has_qa_item_rule = 'plan-qa-item'         in style_text
        has_plan_q_rule  = 'plan-q'               in style_text
        has_amber        = 'd29922'               in style_text
        has_display_blk  = 'display: block'       in style_text

        safe_print(f'  .plan-questions-label rule present : {has_label_rule}')
        safe_print(f'  .plan-qa-item rule present         : {has_qa_item_rule}')
        safe_print(f'  .plan-q rule present               : {has_plan_q_rule}')
        safe_print(f'  Amber colour (#d29922) present     : {has_amber}')
        safe_print(f'  display:block present              : {has_display_blk}')

        check('Inline style — .plan-questions-label rule', has_label_rule, style_text[:300])
        check('Inline style — .plan-qa-item rule',         has_qa_item_rule)
        check('Inline style — .plan-q rule',               has_plan_q_rule)
        check('Inline style — amber colour',               has_amber)
        check('Inline style — display:block rule',         has_display_blk)
        page.screenshot(path='/tmp/plan_ui_01_page.png')

        # ── TEST 2: Questions path — plan card structure ────────────────────
        safe_print('\n--- TEST 2: Plan card with questions ---')

        page.evaluate(SETUP_OBSERVER_JS)

        page.fill('#odradek-input', "Can you create a valentine's day newsletter?")
        page.click('#odradek-send')

        try:
            page.wait_for_selector('#odradek-send[disabled]', timeout=3000)
        except Exception:
            pass

        # Wait for full response (plan fires stopBusy; done fires setBusy(false))
        page.wait_for_selector('#odradek-send:not([disabled])', timeout=90000)
        page.wait_for_timeout(400)

        page.screenshot(path='/tmp/plan_ui_02_after_response.png')

        cap = page.evaluate('window.__planCapture')
        safe_print(f'  Plan capture: {cap}')

        if cap is None:
            safe_print('  MutationObserver did not fire — plan may have had no questions.')
            safe_print('  Checking finalised text for fallback evidence...')
            final_text = page.eval_on_selector_all(
                '.exchange-main .msg-body',
                "els => els.map(e => e.textContent)"
            )
            last_text = final_text[-1] if final_text else ''
            safe_print(f'  Last reply text (100): {last_text[:100]!r}')
            plan_in_text = ('execution plan' in last_text.lower() or
                            'step' in last_text.lower() or '▣' in last_text)
            check('Plan content visible in finalized reply', plan_in_text, last_text[:150])
            # Skip structural checks — no capture
            for name in [
                'plan-questions div present in card',
                'plan-questions-label div present',
                'plan-qa-item divs present',
                'Input fields in questions',
            ]:
                safe_print(f'  SKIP: {name} (no capture)')
        else:
            has_pq  = 'plan-questions' in cap['outerHtml']
            has_lbl = cap['hasLabel']
            has_qa  = cap['hasQaItem']
            n_inp   = cap['inputCount']
            n_q     = cap['questionCount']

            safe_print(f'  plan-questions class in HTML : {has_pq}')
            safe_print(f'  plan-questions-label present : {has_lbl}')
            safe_print(f'  plan-qa-item present         : {has_qa}')
            safe_print(f'  Input fields                 : {n_inp}')
            safe_print(f'  .plan-q elements             : {n_q}')

            check('plan-questions div present in card',   has_pq,  cap['outerHtml'][:200])
            check('plan-questions-label div present',     has_lbl)
            check('plan-qa-item divs present',            has_qa)
            check('Input fields in questions',            n_inp > 0, n_inp)

        # ── TEST 3: Button text ─────────────────────────────────────────────
        safe_print('\n--- TEST 3: Approve button text ---')

        if cap and cap.get('approveBtnText'):
            btn_text = cap['approveBtnText']
            safe_print(f'  Button text: {btn_text!r}')
            is_submit  = 'Submit'  in btn_text
            not_approve = 'Approve' not in btn_text
            check('Button says "Submit & Execute"',          is_submit,   btn_text)
            check('Button does NOT say "Approve & Execute"', not_approve, btn_text)
        else:
            safe_print('  Approve button not captured (no questions path or no capture).')
            safe_print('  SKIP: button text checks')

        # ── TEST 4: No separate .plan-actions when questions present ────────
        safe_print('\n--- TEST 4: No separate plan-actions div when questions present ---')

        if cap and not cap.get('noQuestionsPath') and cap.get('inputCount', 0) > 0:
            sep_actions = cap.get('separatePlanActions')
            safe_print(f'  Separate .plan-actions sibling: {sep_actions}')
            check('No separate .plan-actions when questions present', not sep_actions, sep_actions)
        else:
            safe_print('  SKIP: no questions were generated or no capture')

        # ── TEST 5: No-questions path still shows Approve & Execute ─────────
        safe_print('\n--- TEST 5: No-questions path — Approve & Execute shown ---')

        page.evaluate(SETUP_OBSERVER_JS)

        # Use a very simple request unlikely to generate questions
        page.fill('#odradek-input', 'List my contacts')
        page.click('#odradek-send')

        try:
            page.wait_for_selector('#odradek-send[disabled]', timeout=3000)
        except Exception:
            pass

        page.wait_for_selector('#odradek-send:not([disabled])', timeout=60000)
        page.wait_for_timeout(400)

        cap2 = page.evaluate('window.__planCapture')
        page.screenshot(path='/tmp/plan_ui_05_no_questions.png')

        if cap2 and cap2.get('noQuestionsPath'):
            btn2 = cap2.get('approveBtnText', '')
            safe_print(f'  No-questions button text: {btn2!r}')
            check('No-questions path shows Approve & Execute', 'Approve' in (btn2 or ''), btn2)
        else:
            # "List my contacts" likely won't trigger plan mode at all (not complex).
            # That is fine — test 5 is a best-effort check.
            safe_print('  Plan mode did not trigger for simple request — expected. SKIP.')

        # ── Screenshot of final UI state ────────────────────────────────────
        page.screenshot(path='/tmp/plan_ui_final.png', full_page=True)

        # ── Summary ─────────────────────────────────────────────────────────
        safe_print(f'\n{"="*55}')
        safe_print(f'Plan Mode UI Tests: {pass_count} passed, {fail_count} failed')
        safe_print(f'{"="*55}')
        safe_print('Screenshots: /tmp/plan_ui_*.png')

        browser.close()

    return pass_count, fail_count


if __name__ == '__main__':
    passed, failed = run()
    sys.exit(0 if failed == 0 else 1)
