"""
UAT Runner — executes all OdradekAI UAT suites and prints a summary table.

Usage:
    python run_uat.py

Individual suites can still be run directly:
    python test_uat_contacts.py
"""
import sys
import importlib
import time
sys.stdout.reconfigure(encoding='utf-8')

SUITES = [
    ('Core UI',     'test_uat_core'),
    ('Contacts',    'test_uat_contacts'),
    ('Emails',      'test_uat_emails'),
    ('Segments',    'test_uat_segments'),
    ('Campaigns',   'test_uat_campaigns'),
    ('Plan Mode',   'test_uat_plan_mode'),
    ('Navigation',  'test_uat_navigation'),
]


def safe_print(msg):
    try:
        print(msg)
    except UnicodeEncodeError:
        print(msg.encode('ascii', 'replace').decode('ascii'))


def run_suite(label, module_name):
    safe_print(f'\n{"#"*60}')
    safe_print(f'# Suite: {label}  ({module_name})')
    safe_print(f'{"#"*60}')
    t0 = time.time()
    try:
        mod = importlib.import_module(module_name)
        passed, failed = mod.run()
    except Exception as exc:
        safe_print(f'  ERROR running {module_name}: {exc}')
        passed, failed = 0, 1
    elapsed = time.time() - t0
    return passed, failed, elapsed


def main():
    results = []
    total_pass = 0
    total_fail = 0

    for label, module_name in SUITES:
        passed, failed, elapsed = run_suite(label, module_name)
        results.append((label, passed, failed, elapsed))
        total_pass += passed
        total_fail += failed

    total = total_pass + total_fail

    safe_print(f'\n\n{"="*52}')
    safe_print(f'  UAT Suite Results')
    safe_print(f'{"="*52}')
    safe_print(f'  {"Suite":<16} {"Pass":>5} {"Fail":>5} {"Time":>7}')
    safe_print(f'  {"-"*40}')
    for label, passed, failed, elapsed in results:
        status = 'OK' if failed == 0 else 'FAIL'
        safe_print(
            f'  {label:<16} {passed:>5} {failed:>5} {elapsed:>6.1f}s  [{status}]'
        )
    safe_print(f'  {"-"*40}')
    safe_print(f'  {"TOTAL":<16} {total_pass:>5} {total_fail:>5}')
    safe_print(f'{"="*52}')

    if total_fail == 0:
        safe_print(f'\n  ALL {total} TESTS PASSED')
    else:
        safe_print(f'\n  {total_pass}/{total} passed  ({total_fail} failed)')

    safe_print('')
    return 0 if total_fail == 0 else 1


if __name__ == '__main__':
    sys.exit(main())
