#!/usr/bin/env python3
"""
seed_mautic.py — Mautic test data seeder
Inserts realistic contacts, companies, segments, and engagement data
directly into MySQL via docker exec. No external dependencies required.

Usage:
    python scripts/seed_mautic.py
"""

import subprocess
import random
import sys
from datetime import datetime, timedelta

# ── Configuration ──────────────────────────────────────────────────────────────
DB_CONTAINER = "hackathon-db-1"
DB_USER      = "mautic"
DB_PASS      = "mautic"
DB_NAME      = "mautic"

random.seed(42)  # reproducible data

# ── Fake data pools ────────────────────────────────────────────────────────────
FIRST_NAMES = [
    "Anna", "Lena", "Sophie", "Julia", "Maria", "Laura", "Sarah", "Emma",
    "Nina", "Clara", "Thomas", "Michael", "Stefan", "Andreas", "Markus",
    "David", "Alexander", "Peter", "Johannes", "Tobias", "James", "Emily",
    "William", "Olivia", "John", "Ava", "Robert", "Isabella", "Charles",
    "Mia", "Hans", "Ingrid", "Lars", "Astrid", "Klaus", "Brigitte",
    "Francois", "Marie", "Pierre", "Isabelle",
]

LAST_NAMES = [
    "Müller", "Schmidt", "Schneider", "Fischer", "Weber", "Meyer", "Wagner",
    "Becker", "Schulz", "Hoffmann", "Maier", "Koch", "Richter", "Klein",
    "Wolf", "Schröder", "Neumann", "Schwarz", "Zimmermann", "Braun",
    "Smith", "Johnson", "Williams", "Brown", "Jones", "Garcia", "Miller",
    "Davis", "Wilson", "Taylor", "Gruber", "Huber", "Steiner", "Moser",
    "Mayer", "Leitner", "Hofer", "Reiter", "Pichler", "Bauer",
]

COMPANIES_DATA = [
    ("TechVision GmbH",        "Technology",   "Vienna",      "Austria"),
    ("DataFlow AG",            "Technology",   "Zurich",      "Switzerland"),
    ("CloudBase Solutions",    "Technology",   "Amsterdam",   "Netherlands"),
    ("MediCare Plus",          "Healthcare",   "Berlin",      "Germany"),
    ("HealthBridge GmbH",      "Healthcare",   "Vienna",      "Austria"),
    ("RetailPro Europe",       "Retail",       "Munich",      "Germany"),
    ("ShopEasy NL",            "Retail",       "Amsterdam",   "Netherlands"),
    ("Fashion Forward Ltd",    "Retail",       "London",      "UK"),
    ("FinanzPartner AG",       "Finance",      "Zurich",      "Switzerland"),
    ("CreditFirst GmbH",       "Finance",      "Frankfurt",   "Germany"),
    ("InvestWise Ltd",         "Finance",      "London",      "UK"),
    ("LogiSwift GmbH",         "Logistics",    "Vienna",      "Austria"),
    ("FreightMaster AG",       "Logistics",    "Zurich",      "Switzerland"),
    ("QuickDeliver NL",        "Logistics",    "Amsterdam",   "Netherlands"),
    ("EduLearn Platform",      "Education",    "Berlin",      "Germany"),
    ("CampusTech GmbH",        "Education",    "Vienna",      "Austria"),
    ("GreenEnergy Solutions",  "Energy",       "Hamburg",     "Germany"),
    ("SolarPeak AG",           "Energy",       "Zurich",      "Switzerland"),
    ("MediaPulse GmbH",        "Media",        "Munich",      "Germany"),
    ("AdReach Ltd",            "Media",        "London",      "UK"),
    ("TravelEasy GmbH",        "Travel",       "Vienna",      "Austria"),
    ("GlobalTrip Ltd",         "Travel",       "London",      "UK"),
    ("FoodTech Innovations",   "Food & Bev",   "Paris",       "France"),
    ("Midwest Dynamics Inc",   "Technology",   "Chicago",     "USA"),
    ("Atlantic Ventures LLC",  "Finance",      "New York",    "USA"),
]

CITY_COUNTRY = [
    ("Vienna",    "AT", "Austria"),
    ("Graz",      "AT", "Austria"),
    ("Linz",      "AT", "Austria"),
    ("Berlin",    "DE", "Germany"),
    ("Munich",    "DE", "Germany"),
    ("Hamburg",   "DE", "Germany"),
    ("Frankfurt", "DE", "Germany"),
    ("Zurich",    "CH", "Switzerland"),
    ("Geneva",    "CH", "Switzerland"),
    ("Amsterdam", "NL", "Netherlands"),
    ("Rotterdam", "NL", "Netherlands"),
    ("London",    "GB", "UK"),
    ("Manchester","GB", "UK"),
    ("Paris",     "FR", "France"),
    ("Lyon",      "FR", "France"),
    ("New York",  "US", "USA"),
    ("Chicago",   "US", "USA"),
    ("Boston",    "US", "USA"),
]

EMAIL_DOMAINS = [
    "gmail.com", "outlook.com", "yahoo.com", "hotmail.com",
    "protonmail.com", "icloud.com",
]

SEGMENTS = [
    ("Newsletter Subscribers",  "newsletter-subscribers",  "Newsletter"),
    ("Hot Leads",               "hot-leads",               "Hot Leads"),
    ("Customers",               "customers",               "Customers"),
    ("Cold Leads",              "cold-leads",              "Cold Leads"),
    ("Austrian Contacts",       "austrian-contacts",       "Austrian Contacts"),
    ("SMB Segment",             "smb-segment",             "SMB"),
    ("Enterprise Segment",      "enterprise-segment",      "Enterprise"),
    ("Churned Contacts",        "churned-contacts",        "Churned"),
]

EMAIL_TEMPLATES = [
    ("Welcome Email",            "welcome-email"),
    ("Monthly Newsletter #1",    "monthly-newsletter-1"),
    ("Product Announcement",     "product-announcement"),
    ("Re-engagement Campaign",   "re-engagement-campaign"),
    ("Customer Satisfaction",    "customer-satisfaction"),
]

POINT_ACTIONS = [
    ("page.hit",   "Page Visit"),
    ("email.open", "Email Open"),
    ("form.submit","Form Submission"),
    ("url.hit",    "URL Click"),
]

PAGE_URLS = [
    "/landing/welcome",
    "/landing/summer-sale",
    "/landing/product-demo",
    "/landing/newsletter-signup",
    "/landing/contact-us",
    "/landing/free-trial",
    "/landing/webinar-registration",
    "/landing/case-study",
    "/landing/pricing",
]

FORM_NAMES = [
    ("Contact Us",          "contact-us"),
    ("Newsletter Signup",   "newsletter-signup"),
    ("Demo Request",        "demo-request"),
]

AUDIT_EVENTS = [
    ("lead",     "lead",     "create"),
    ("lead",     "lead",     "update"),
    ("lead",     "lead",     "identified"),
    ("email",    "email",    "create"),
    ("email",    "email",    "update"),
    ("campaign", "campaign", "create"),
    ("page",     "page",     "create"),
    ("form",     "form",     "create"),
    ("asset",    "asset",    "create"),
    ("segment",  "segment",  "create"),
]


# ── MySQL helpers ──────────────────────────────────────────────────────────────

def mysql(sql: str) -> str:
    """Run a SQL string via docker exec and return stdout."""
    result = subprocess.run(
        [
            "docker", "exec", DB_CONTAINER,
            "mysql",
            f"-u{DB_USER}",
            f"-p{DB_PASS}",
            DB_NAME,
            "--silent",
            "-e", sql,
        ],
        capture_output=True,
        text=True,
    )
    if result.returncode != 0:
        print(f"  [ERROR] MySQL error:\n{result.stderr.strip()}", file=sys.stderr)
        sys.exit(1)
    return result.stdout.strip()


def mysql_batch(stmts: list[str]) -> None:
    """Join multiple INSERT statements and run as one call."""
    if not stmts:
        return
    combined = " ".join(stmts)
    mysql(combined)


def esc(s) -> str:
    """Escape a value for inline SQL (single-quoted string)."""
    if s is None:
        return "NULL"
    return "'" + str(s).replace("\\", "\\\\").replace("'", "\\'") + "'"


def rand_date(days_back_min=1, days_back_max=730) -> str:
    """Return a random datetime string within the given window."""
    delta = random.randint(days_back_min, days_back_max)
    dt = datetime.now() - timedelta(days=delta)
    return dt.strftime("%Y-%m-%d %H:%M:%S")


def rand_date_after(base: str, min_hours=1, max_hours=72) -> str:
    """Return a datetime string shortly after the given base datetime."""
    base_dt = datetime.strptime(base, "%Y-%m-%d %H:%M:%S")
    delta = timedelta(hours=random.randint(min_hours, max_hours))
    return (base_dt + delta).strftime("%Y-%m-%d %H:%M:%S")


def slugify(name: str) -> str:
    return name.lower().replace(" ", "-").replace("_", "-").replace("&", "and")


def domain_from_company(name: str) -> str:
    slug = slugify(name).replace("-", "")[:12]
    tld = random.choice([".com", ".io", ".eu", ".de", ".at"])
    return slug + tld


# ── Seeder functions ───────────────────────────────────────────────────────────

def get_admin_id() -> int:
    row = mysql("SELECT id FROM users LIMIT 1;")
    if not row:
        print("  [WARN] No users found — using 1 as admin_id", file=sys.stderr)
        return 1
    return int(row.strip())


def seed_companies(admin_id: int) -> list[int]:
    print("  Inserting companies...")
    stmts = []
    now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    for name, industry, city, country in COMPANIES_DATA:
        website = "https://www." + domain_from_company(name)
        stmts.append(
            f"INSERT INTO companies (companyname, companywebsite, companycity, companycountry, "
            f"companyindustry, is_published, date_added, date_modified, created_by, created_by_user) "
            f"VALUES ({esc(name)}, {esc(website)}, {esc(city)}, {esc(country)}, "
            f"{esc(industry)}, 1, {esc(now)}, {esc(now)}, {admin_id}, {esc('admin')});"
        )
    mysql_batch(stmts)

    rows = mysql("SELECT id FROM companies ORDER BY id DESC LIMIT 25;")
    ids = [int(r) for r in rows.splitlines() if r.strip().isdigit()]
    ids.reverse()
    print(f"    -> {len(ids)} companies inserted (IDs {ids[0]}–{ids[-1]})")
    return ids


def seed_contacts(admin_id: int) -> list[dict]:
    print("  Inserting contacts...")
    contacts = []
    stmts = []

    used_emails = set()
    for i in range(150):
        first = random.choice(FIRST_NAMES)
        last  = random.choice(LAST_NAMES)
        city_info = random.choice(CITY_COUNTRY)
        city, state_code, country = city_info

        # unique email
        for _ in range(20):
            domain = random.choice(EMAIL_DOMAINS)
            suffix = str(random.randint(10, 999))
            email  = f"{first.lower()}.{last.lower()}{suffix}@{domain}"
            if email not in used_emails:
                used_emails.add(email)
                break

        phone   = f"+{random.randint(1,49)}{random.randint(100000000,999999999)}"
        points  = random.randint(0, 250)
        zipcode = str(random.randint(10000, 99999))
        date_added = rand_date(1, 730)

        contacts.append({
            "first": first, "last": last, "email": email,
            "phone": phone, "city": city, "country": country,
            "state": state_code, "zipcode": zipcode,
            "points": points, "date_added": date_added,
        })
        stmts.append(
            f"INSERT INTO leads (firstname, lastname, email, phone, city, state, country, "
            f"zipcode, points, is_published, date_added, date_modified, date_identified, created_by, created_by_user) "
            f"VALUES ({esc(first)}, {esc(last)}, {esc(email)}, {esc(phone)}, "
            f"{esc(city)}, {esc(state_code)}, {esc(country)}, {esc(zipcode)}, "
            f"{points}, 1, {esc(date_added)}, {esc(date_added)}, {esc(date_added)}, {admin_id}, {esc('admin')});"
        )

    # Run in batches of 30 to keep command length manageable
    batch_size = 30
    for i in range(0, len(stmts), batch_size):
        mysql_batch(stmts[i:i+batch_size])

    rows = mysql("SELECT id FROM leads ORDER BY id DESC LIMIT 150;")
    ids = [int(r) for r in rows.splitlines() if r.strip().isdigit()]
    ids.reverse()
    print(f"    -> {len(ids)} contacts inserted (IDs {ids[0]}–{ids[-1]})")

    for i, cid in enumerate(ids):
        if i < len(contacts):
            contacts[i]["id"] = cid
    return contacts


def seed_company_links(contacts: list[dict], company_ids: list[int]) -> None:
    print("  Linking contacts to companies...")
    stmts = []
    # Give 70% of contacts a company
    for c in contacts:
        if random.random() < 0.70:
            cid = random.choice(company_ids)
            stmts.append(
                f"INSERT IGNORE INTO companies_leads (company_id, lead_id, is_primary, date_added) "
                f"VALUES ({cid}, {c['id']}, 1, {esc(c['date_added'])});"
            )
    mysql_batch(stmts)
    print(f"    -> {len(stmts)} company-contact links inserted")


def seed_segments(admin_id: int) -> list[int]:
    print("  Inserting segments...")
    now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    stmts = []
    empty_filter = "a:0:{}"
    for name, alias, public_name in SEGMENTS:
        stmts.append(
            f"INSERT INTO lead_lists (name, alias, public_name, is_global, is_published, "
            f"is_preference_center, filters, date_added, date_modified, created_by, created_by_user) "
            f"VALUES ({esc(name)}, {esc(alias)}, {esc(public_name)}, 1, 1, "
            f"0, {esc(empty_filter)}, {esc(now)}, {esc(now)}, {admin_id}, {esc('admin')});"
        )
    mysql_batch(stmts)

    rows = mysql(f"SELECT id FROM lead_lists ORDER BY id DESC LIMIT {len(SEGMENTS)};")
    ids = [int(r) for r in rows.splitlines() if r.strip().isdigit()]
    ids.reverse()
    print(f"    -> {len(ids)} segments inserted")
    return ids


def seed_segment_members(contacts: list[dict], segment_ids: list[int]) -> None:
    print("  Assigning contacts to segments...")
    now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    stmts = []
    seg_names = [s[0] for s in SEGMENTS]

    for c in contacts:
        assigned = set()

        # Austrian contacts → Austrian segment
        if c["country"] == "Austria" and len(segment_ids) >= 5:
            idx = 4  # "Austrian Contacts"
            assigned.add(idx)

        # Points-based: hot leads (>150), cold (<30), churned (0 points, old)
        if c["points"] > 150:
            assigned.add(1)  # Hot Leads
        elif c["points"] < 30:
            assigned.add(3)  # Cold Leads

        # Random segments: newsletter (60%), customers (30%), SMB/Enterprise (20% each)
        if random.random() < 0.60:
            assigned.add(0)  # Newsletter
        if random.random() < 0.30:
            assigned.add(2)  # Customers
        if random.random() < 0.20:
            assigned.add(5)  # SMB
        if random.random() < 0.15:
            assigned.add(6)  # Enterprise
        if random.random() < 0.10:
            assigned.add(7)  # Churned

        for idx in assigned:
            if idx < len(segment_ids):
                stmts.append(
                    f"INSERT IGNORE INTO lead_lists_leads (leadlist_id, lead_id, date_added, manually_added, manually_removed) "
                    f"VALUES ({segment_ids[idx]}, {c['id']}, {esc(now)}, 0, 0);"
                )

    batch_size = 50
    for i in range(0, len(stmts), batch_size):
        mysql_batch(stmts[i:i+batch_size])
    print(f"    -> {len(stmts)} segment memberships inserted")


def seed_emails(admin_id: int) -> list[int]:
    print("  Inserting email templates...")
    now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    stmts = []
    for name, alias in EMAIL_TEMPLATES:
        subject = name
        body = (
            f"<p>Dear {{{{lead.firstname}}}},</p>"
            f"<p>Thank you for your interest. {name} content goes here.</p>"
            f"<p>Best regards,<br>The Team</p>"
        )
        stmts.append(
            f"INSERT INTO emails (name, subject, email_type, lang, is_published, "
            f"read_count, sent_count, variant_sent_count, variant_read_count, revision, headers, "
            f"date_added, date_modified, created_by, created_by_user, custom_html) "
            f"VALUES ({esc(name)}, {esc(subject)}, {esc('template')}, {esc('en')}, "
            f"1, 0, 0, 0, 0, 1, '{{}}', "
            f"{esc(now)}, {esc(now)}, {admin_id}, {esc('admin')}, {esc(body)});"
        )
    mysql_batch(stmts)

    rows = mysql(f"SELECT id FROM emails ORDER BY id DESC LIMIT {len(EMAIL_TEMPLATES)};")
    ids = [int(r) for r in rows.splitlines() if r.strip().isdigit()]
    ids.reverse()
    print(f"    -> {len(ids)} email templates inserted")
    return ids


def seed_email_stats(contacts: list[dict], email_ids: list[int], segment_ids: list[int]) -> None:
    print("  Inserting email stats (sends + opens)...")
    stmts = []
    total_sends = 0
    total_opens = 0

    for c in contacts:
        # Each contact receives 1–4 emails
        num_sends = random.randint(1, 4)
        chosen_emails = random.sample(email_ids, min(num_sends, len(email_ids)))
        list_id = random.choice(segment_ids) if segment_ids else None

        for eid in chosen_emails:
            date_sent = rand_date(1, 365)
            is_read = 1 if random.random() < 0.40 else 0
            open_count = random.randint(1, 3) if is_read else 0
            date_read_val = esc(rand_date_after(date_sent, 1, 48)) if is_read else "NULL"
            stmts.append(
                f"INSERT INTO email_stats (email_id, lead_id, email_address, list_id, "
                f"date_sent, is_read, open_count, is_failed, viewed_in_browser, retry_count, date_read) "
                f"VALUES ({eid}, {c['id']}, {esc(c['email'])}, "
                f"{'NULL' if list_id is None else list_id}, "
                f"{esc(date_sent)}, {is_read}, {open_count}, 0, 0, 0, {date_read_val});"
            )
            total_sends += 1
            if is_read:
                total_opens += 1

    batch_size = 40
    for i in range(0, len(stmts), batch_size):
        mysql_batch(stmts[i:i+batch_size])
    print(f"    -> {total_sends} sends, {total_opens} opens inserted")


def seed_point_logs(contacts: list[dict], admin_id: int) -> None:
    print("  Inserting point change logs...")
    stmts = []
    now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

    for c in contacts:
        # 0–3 point events per contact
        num_events = random.randint(0, 3)
        for _ in range(num_events):
            action_type, action_name = random.choice(POINT_ACTIONS)
            delta = random.randint(1, 25)
            event_date = rand_date(1, 365)
            stmts.append(
                f"INSERT INTO lead_points_change_log (lead_id, type, event_name, action_name, delta, date_added) "
                f"VALUES ({c['id']}, {esc(action_type)}, {esc(action_name)}, {esc(action_name)}, {delta}, {esc(event_date)});"
            )

    batch_size = 50
    for i in range(0, len(stmts), batch_size):
        mysql_batch(stmts[i:i+batch_size])
    print(f"    -> {len(stmts)} point log entries inserted")


def rand_tracking_id() -> str:
    """Return a random 32-char hex tracking token."""
    return ''.join(random.choices('0123456789abcdef', k=32))


def rand_ip() -> str:
    return f"{random.randint(1,254)}.{random.randint(0,255)}.{random.randint(0,255)}.{random.randint(1,254)}"


def seed_page_hits(contacts: list[dict]) -> None:
    print("  Inserting page hits...")
    stmts = []
    # ~300 hits spread across the last 90 days so the bar chart has visible bars
    sample = random.choices(contacts, k=300)
    for c in sample:
        date_hit = rand_date(1, 90)
        url      = random.choice(PAGE_URLS)
        stmts.append(
            f"INSERT INTO page_hits (lead_id, date_hit, code, tracking_id, url, country, city) "
            f"VALUES ({c['id']}, {esc(date_hit)}, 200, {esc(rand_tracking_id())}, "
            f"{esc(url)}, {esc(c['country'])}, {esc(c['city'])});"
        )
    batch_size = 50
    for i in range(0, len(stmts), batch_size):
        mysql_batch(stmts[i:i+batch_size])
    print(f"    -> {len(stmts)} page hits inserted")


def seed_forms_and_submissions(contacts: list[dict], admin_id: int) -> None:
    print("  Inserting forms + submissions...")
    now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

    # Create forms
    form_stmts = []
    for name, alias in FORM_NAMES:
        form_stmts.append(
            f"INSERT INTO forms (name, alias, post_action, is_published, "
            f"date_added, date_modified, created_by, created_by_user) "
            f"VALUES ({esc(name)}, {esc(alias)}, {esc('return')}, 1, "
            f"{esc(now)}, {esc(now)}, {admin_id}, {esc('admin')});"
        )
    mysql_batch(form_stmts)

    rows = mysql(f"SELECT id FROM forms ORDER BY id DESC LIMIT {len(FORM_NAMES)};")
    form_ids = [int(r) for r in rows.splitlines() if r.strip().isdigit()]

    if not form_ids:
        print("    [WARN] No form IDs retrieved, skipping submissions")
        return

    # Create ~80 submissions spread over past 90 days
    sub_stmts = []
    sample = random.choices(contacts, k=80)
    for c in sample:
        fid        = random.choice(form_ids)
        date_sub   = rand_date(1, 90)
        referer    = random.choice(PAGE_URLS)
        sub_stmts.append(
            f"INSERT INTO form_submissions (form_id, lead_id, date_submitted, referer) "
            f"VALUES ({fid}, {c['id']}, {esc(date_sub)}, {esc(referer)});"
        )
    batch_size = 40
    for i in range(0, len(sub_stmts), batch_size):
        mysql_batch(sub_stmts[i:i+batch_size])
    print(f"    -> {len(form_ids)} forms + {len(sub_stmts)} submissions inserted")


def seed_audit_log(contacts: list[dict], admin_id: int) -> None:
    print("  Inserting audit log entries...")
    stmts = []
    # Use contact IDs as object_ids for lead events; shuffle for variety
    contact_ids = [c['id'] for c in contacts]
    random.shuffle(contact_ids)

    for i in range(200):
        bundle, obj, action = random.choice(AUDIT_EVENTS)
        # Use a real contact id for lead events, otherwise a small random id
        object_id = random.choice(contact_ids) if obj == "lead" else random.randint(1, 20)
        stmts.append(
            f"INSERT INTO audit_log (user_id, user_name, bundle, object, object_id, "
            f"action, details, date_added, ip_address) "
            f"VALUES ({admin_id}, {esc('admin')}, {esc(bundle)}, {esc(obj)}, {object_id}, "
            f"{esc(action)}, {esc('a:0:{}')}, {esc(rand_date(1, 90))}, {esc(rand_ip())});"
        )
    batch_size = 50
    for i in range(0, len(stmts), batch_size):
        mysql_batch(stmts[i:i+batch_size])
    print(f"    -> {len(stmts)} audit log entries inserted")


def seed_upcoming_emails(contacts: list[dict], admin_id: int) -> None:
    print("  Inserting upcoming email campaign schedule...")
    now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

    # Create one campaign
    mysql(
        f"INSERT INTO campaigns (name, is_published, allow_restart, version, "
        f"date_added, date_modified, created_by, created_by_user) "
        f"VALUES ({esc('Demo Email Campaign')}, 1, 0, 1, "
        f"{esc(now)}, {esc(now)}, {admin_id}, {esc('admin')});"
    )
    campaign_id = int(mysql("SELECT id FROM campaigns WHERE name='Demo Email Campaign' ORDER BY id DESC LIMIT 1;"))

    # Create one email.send event on that campaign
    mysql(
        f"INSERT INTO campaign_events (campaign_id, name, type, event_type, "
        f"event_order, properties, failed_count) "
        f"VALUES ({campaign_id}, {esc('Send Welcome Email')}, {esc('email.send')}, "
        f"{esc('action')}, 0, {esc('a:0:{}')}, 0);"
    )
    event_id = int(mysql(f"SELECT id FROM campaign_events WHERE campaign_id={campaign_id} ORDER BY id DESC LIMIT 1;"))

    # Schedule 20 contacts to receive it on future dates (next 30 days)
    sample = random.sample(contacts, min(20, len(contacts)))
    stmts  = []
    for c in sample:
        days_ahead   = random.randint(1, 30)
        trigger_date = (datetime.now() + timedelta(days=days_ahead)).strftime("%Y-%m-%d %H:%M:%S")
        stmts.append(
            f"INSERT IGNORE INTO campaign_lead_event_log "
            f"(event_id, lead_id, campaign_id, rotation, is_scheduled, system_triggered, trigger_date) "
            f"VALUES ({event_id}, {c['id']}, {campaign_id}, 1, 1, 0, {esc(trigger_date)});"
        )
    mysql_batch(stmts)
    print(f"    -> 1 campaign + 1 event + {len(stmts)} scheduled sends inserted")


# ── Main ───────────────────────────────────────────────────────────────────────

def main():
    print("=== Mautic Test Data Seeder ===\n")

    print("[1/9] Checking DB connection...")
    mysql("SELECT 1;")
    print("      DB OK")

    print("[2/9] Getting admin user ID...")
    admin_id = get_admin_id()
    print(f"      admin_id = {admin_id}")

    print("[3/9] Seeding companies...")
    company_ids = seed_companies(admin_id)

    print("[4/9] Seeding contacts...")
    contacts = seed_contacts(admin_id)

    print("[5/9] Linking contacts to companies...")
    seed_company_links(contacts, company_ids)

    print("[6/9] Seeding segments...")
    segment_ids = seed_segments(admin_id)

    print("[7/9] Assigning segment memberships...")
    seed_segment_members(contacts, segment_ids)

    print("[8/9] Seeding email templates + stats...")
    email_ids = seed_emails(admin_id)
    seed_email_stats(contacts, email_ids, segment_ids)

    print("[9/9] Seeding point change logs...")
    seed_point_logs(contacts, admin_id)

    print("[10/13] Seeding page hits (Page Visits widget)...")
    seed_page_hits(contacts)

    print("[11/13] Seeding forms + submissions (Form Submissions widget)...")
    seed_forms_and_submissions(contacts, admin_id)

    print("[12/13] Seeding audit log (Recent Activity widget)...")
    seed_audit_log(contacts, admin_id)

    print("[13/13] Seeding upcoming email schedule (Upcoming Emails widget)...")
    seed_upcoming_emails(contacts, admin_id)

    print("\n=== Seeding complete! ===")
    print("\nVerification query:")
    print("  docker exec hackathon-db-1 mysql -umautic -pmautic mautic -e \\")
    print("    \"SELECT 'contacts' t, COUNT(*) n FROM leads")
    print("     UNION SELECT 'companies', COUNT(*) FROM companies")
    print("     UNION SELECT 'email_stats', COUNT(*) FROM email_stats")
    print("     UNION SELECT 'segments', COUNT(*) FROM lead_lists WHERE is_global=1;\"")

    # Run it automatically
    print("\nCurrent counts:")
    result = mysql(
        "SELECT 'contacts' t, COUNT(*) n FROM leads "
        "UNION SELECT 'companies', COUNT(*) FROM companies "
        "UNION SELECT 'email_stats', COUNT(*) FROM email_stats "
        "UNION SELECT 'segments', COUNT(*) FROM lead_lists WHERE is_global=1;"
    )
    for line in result.splitlines():
        print(f"  {line}")


if __name__ == "__main__":
    main()
