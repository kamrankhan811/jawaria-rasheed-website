"""Build the site content.

Outputs:
  wp-content/plugins/jawaria-clinic/content/pages.json   (read by Tools → Clinic Site Setup)
  wp-content/plugins/jawaria-clinic/content/posts.json   (draft articles)
  wp-content/plugins/jawaria-clinic/assets/images/*.jpg  (placeholder photos)
  preview/*.html                                         (static look-alike preview)

Run:  python tools/build.py
"""

import json
import re
import os
import sys

sys.path.insert(0, os.path.dirname(__file__))

from elementor import (button, checklist, column, fallback_text, heading, image,  # noqa: E402
                       render_preview, section, shortcode, text, toggle)
from content_services import SERVICES  # noqa: E402
from content_posts import POSTS  # noqa: E402

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
PLUGIN = os.path.join(ROOT, "wp-content", "plugins", "jawaria-clinic")
CHILD = os.path.join(ROOT, "wp-content", "themes", "jawaria-child")


# ------------------------------------------------------------------ shared blocks

def cta_band(title="Ready to talk to Dr. Jawaria?", sub="Send an appointment request online or message the clinic on WhatsApp. Our team will confirm a time with you."):
    return section([
        heading("Appointments", "p", "jr-eyebrow jr-center"),
        heading(title, "h2", "jr-h2 jr-center"),
        text(f"<p>{sub}</p>", "jr-center jr-narrow"),
        shortcode('[jr_cta_buttons style="light" align="center"]'),
    ], "jr-cta-band")


def page_hero(eyebrow, title, lead, buttons=True):
    w = [heading(eyebrow, "p", "jr-eyebrow"), heading(title, "h1"), text(f"<p>{lead}</p>", "jr-lead jr-narrow-left")]
    if buttons:
        w.append(shortcode("[jr_cta_buttons]"))
    return section(w, "jr-page-hero")


# ------------------------------------------------------------------ pages

def home():
    """Homepage — section order follows the reference layout."""
    pills = "".join(
        f'<a class="jr-pill" href="/services/{slug}/">{label}</a>' for slug, label in [
            ("gynecology-consultation", "Gynecology"), ("infertility-evaluation", "Fertility &amp; IUI"),
            ("pregnancy-care", "Pregnancy Care"), ("pcos", "PCOS"), ("endometriosis", "Endometriosis"),
            ("fibroids", "Fibroids"), ("menopause", "Menopause")])
    return [
        # 1. Hero with highlight-number cards
        section([
            column([
                heading("Consultant Gynecologist · Women’s Health &amp; IUI Specialist".replace("&amp;", "&"), "p", "jr-eyebrow"),
                heading("Expert gynecology and fertility care for every stage of a woman’s life", "h1"),
                text("<p>Compassionate, personalised and professional care from Dr. Jawaria Rasheed — with more than [jr_experience] years of experience in gynecology, women’s health and IUI.</p>", "jr-lead"),
                shortcode("[jr_cta_buttons]"),
            ], 58),
            column([image("doctor", "Dr. Jawaria Rasheed")], 42, "jr-hero-photo"),
        ], "jr-hero", middle=True),
        section([shortcode("[jr_stats]")], "jr-section jr-section--stats"),

        # 2. Intro with pull-quote
        section([
            column([
                heading("Welcome", "p", "jr-eyebrow"),
                heading("Helping women understand their health and make informed decisions", "h2", "jr-h2"),
            ], 45),
            column([
                text("<blockquote class=\"jr-quote\">“Every woman deserves to be listened to, to understand her options and to receive care that fits her life.”</blockquote>"),
                text("<p>Dr. Jawaria Rasheed is an experienced gynecologist dedicated to compassionate, personalised and professional care for women at every stage of life — from first periods to pregnancy, fertility and menopause.</p><p>She believes in building trust through clear communication, respectful care and a patient-centred approach.</p>"),
                button("Read her full story", "/about/", "jr-btn-ghost"),
            ], 55),
        ], "jr-section"),

        # 3+4. Areas of focus + photo
        section([
            column([
                heading("Areas of focus", "p", "jr-eyebrow"),
                heading("Complete care in gynecology and fertility", "h2", "jr-h2"),
                checklist([
                    "Gynecological consultations",
                    "Reproductive health",
                    "Fertility evaluation for couples",
                    "IUI (Intrauterine Insemination)",
                    "Women’s wellness, from adolescence to menopause",
                ]),
                text("<p>Each plan starts with careful listening and the right tests, so treatment is tailored to your needs — never one-size-fits-all.</p>"),
            ], 55),
            column([image("clinic", "Clinic consultation room", "jr-photo jr-photo--wide")], 45),
        ], "jr-section jr-section--tint", middle=True),

        # 5. Feature: fertility & IUI
        section([
            column([
                heading("Fertility &amp; IUI".replace("&amp;", "&"), "p", "jr-eyebrow"),
                heading("Trying to conceive? Start with clear answers.", "h2", "jr-h2"),
                text("<p>Fertility concerns can feel overwhelming. Dr. Jawaria evaluates both partners, then recommends the simplest effective step — from ovulation support and follicle monitoring to IUI. When another option such as IVF is more suitable, she will explain why and guide you.</p>"),
                button("Learn about IUI", "/services/iui-treatment/", "jr-btn-primary"),
            ], 55),
            column([
                heading("Your fertility journey", "h3"),
                checklist([
                    "1. Consultation for both partners",
                    "2. Tests: hormones, ultrasound, semen analysis",
                    "3. A personalised plan, explained clearly",
                    "4. Treatment such as ovulation induction or IUI",
                    "5. Follow-up and support at every step",
                ]),
            ], 45, "jr-card"),
        ], "jr-section", middle=True),

        # 6+7. Category links + service cards
        section([
            heading("Our services", "p", "jr-eyebrow jr-center"),
            heading("How Dr. Jawaria can help", "h2", "jr-h2 jr-center"),
            text(f'<div class="jr-pills jr-center">{pills}</div>'),
            shortcode("[jr_services_grid]"),
        ], "jr-section jr-section--surface"),

        # 8. Doctor profile
        section([
            column([image("doctor", "Dr. Jawaria Rasheed")], 40),
            column([
                heading("Meet your doctor", "p", "jr-eyebrow"),
                heading("Dr. Jawaria Rasheed", "h2", "jr-h2"),
                text("<p class=\"jr-credentials\">Consultant Gynecologist | Women’s Health &amp; IUI Specialist<br>[jr_qualifications]</p>"),
                text("<p>With over [jr_experience] years of experience in gynecology, women’s healthcare and IUI, Dr. Jawaria’s mission is to support women’s health, improve reproductive well-being and help couples navigate their fertility journey with confidence and care.</p>"),
                shortcode("[jr_rating]"),
                button("Know more", "/about/", "jr-btn-primary"),
            ], 60),
        ], "jr-section", middle=True),

        # 9. Patient reviews
        section([
            heading("Patient reviews", "p", "jr-eyebrow jr-center"),
            heading("What our patients say", "h2", "jr-h2 jr-center"),
            shortcode('[jr_reviews count="3"]'),
            button("See all reviews", "/reviews/", "jr-btn-ghost", "center"),
        ], "jr-section jr-section--tint"),

        # 10. YouTube
        section([
            heading("Videos", "p", "jr-eyebrow jr-center"),
            heading("Learn from Dr. Jawaria on YouTube", "h2", "jr-h2 jr-center"),
            text("<p>Short, simple videos on women’s health, fertility and pregnancy.</p>", "jr-lead jr-center"),
            shortcode('[jr_videos count="3"]'),
        ], "jr-section"),

        # 11. Blogs
        section([
            heading("Health articles", "p", "jr-eyebrow jr-center"),
            heading("Featured articles", "h2", "jr-h2 jr-center"),
            shortcode('[jr_latest_posts count="3"]'),
            button("View all articles", "/blog/", "jr-btn-ghost", "center"),
        ], "jr-section jr-section--surface"),

        # 12. CTA
        cta_band(),

        # 13. Location
        section([
            column([
                heading("Location", "p", "jr-eyebrow"),
                heading("Visit the clinic", "h2", "jr-h2"),
                shortcode("[jr_clinic_info]"),
                shortcode("[jr_hours]"),
            ], 40),
            column([
                shortcode("[jr_map]"),
                heading("Patients visit us from across South Punjab", "h3"),
                shortcode("[jr_areas]"),
            ], 60),
        ], "jr-section"),
    ], None


def reviews_page():
    return [
        page_hero("Patient Guide", "Patient reviews", "Feedback shared by patients, published with their permission.", buttons=False),
        section([shortcode('[jr_reviews count="0"]')], "jr-section"),
        cta_band(),
    ]


def videos_page():
    return [
        page_hero("Patient Guide", "Videos", "Short, simple videos from Dr. Jawaria on women’s health, fertility and pregnancy.", buttons=False),
        section([shortcode('[jr_videos count="0"]')], "jr-section"),
        cta_band(),
    ]


def section_cards(cards):
    """Inline 3-column card row rendered as text (keeps editing simple in Elementor)."""
    html_cards = "".join(
        f'<div class="jr-mini-card"><h3>{t}</h3><p>{d}</p></div>' for t, d in cards
    )
    return text(f'<div class="jr-mini-cards">{html_cards}</div>')


def about():
    return [
        section([
            column([
                heading("About", "p", "jr-eyebrow"),
                heading("Dr. Jawaria Rasheed", "h1"),
                text("<p><strong>Consultant Gynecologist | Women’s Health &amp; IUI Specialist</strong><br>[jr_qualifications]</p>", "jr-lead"),
                text("<p>Dr. Jawaria Rasheed is an experienced gynecologist with over [jr_experience] years of experience in gynecology, women’s healthcare and Intrauterine Insemination (IUI). She is dedicated to providing compassionate, personalised and professional care to women at every stage of life.</p>"),
                shortcode("[jr_cta_buttons]"),
            ], 55),
            column([image("doctor", "Dr. Jawaria Rasheed")], 45),
        ], "jr-page-hero", middle=True),

        section([
            column([
                heading("Her approach", "h2", "jr-h2"),
                text("<p>With extensive experience in gynecological care and fertility support, Dr. Jawaria focuses on helping women understand their health, make informed decisions and receive treatment tailored to their individual needs.</p><p>She believes in building trust through clear communication, respectful care and a patient-centred approach. Her mission is to support women’s health, improve reproductive well-being, and help individuals and couples navigate their fertility journey with confidence and care.</p>", "jr-prose"),
                heading("Areas of focus", "h3"),
                checklist([
                    "Gynecological consultations",
                    "Reproductive health",
                    "Fertility evaluation",
                    "IUI procedures",
                    "Women’s wellness",
                ]),
            ], 60),
            column([
                heading("Qualifications & experience", "h3"),
                text("<p>[jr_qualifications]</p><ul><li>[jr_experience]+ years in gynecology and women’s health</li><li>Add hospital appointments here</li><li>Add professional memberships here</li><li>Add training / certifications (e.g. IUI, ultrasound) here</li></ul>", "jr-prose"),
                button("Book an appointment", "/book-appointment/", "jr-btn-primary"),
            ], 40, "jr-card"),
        ], "jr-section"),

        section([
            heading("Clinic", "p", "jr-eyebrow"),
            heading("Where and when to see Dr. Jawaria", "h2", "jr-h2"),
            section_cols_info(),
        ], "jr-section jr-section--tint"),

        cta_band(),
    ]


def section_cols_info():
    return text("<div class=\"jr-two\"><div>[jr_clinic_info]</div><div>[jr_hours]</div></div>")


def services_hub():
    return [
        page_hero("Services", "Gynecology, pregnancy and fertility services",
                  "Care for women at every stage of life — from first periods to pregnancy, fertility and menopause. Select a service to learn what it involves."),
        section([shortcode("[jr_services_grid]")], "jr-section"),
        section([
            column([
                heading("Not sure which service you need?", "h2", "jr-h2"),
                text("<p>Book a general consultation. Dr. Jawaria will listen to your concerns and guide you to the right tests and treatment.</p>"),
            ], 60),
            column([shortcode("[jr_cta_buttons]")], 40),
        ], "jr-section jr-section--beige", middle=True),
    ]


def service_page(s):
    by_slug = {x["slug"]: x for x in SERVICES}
    related = [by_slug[r] for r in s.get("related", []) if r in by_slug]

    left = [
        heading(f"About {s['title']}", "h2", "jr-h2"),
        text(s["overview"], "jr-prose"),
        heading(s["signs_title"], "h2", "jr-h2"),
        checklist(s["signs"]),
    ]
    if s.get("causes"):
        left += [heading(s["causes_title"], "h2", "jr-h2"), text(s["causes"], "jr-prose")]
    left += [
        heading(s["diagnosis_title"], "h2", "jr-h2"),
        text("<ul>" + "".join(f"<li>{d}</li>" for d in s["diagnosis"]) + "</ul>", "jr-prose"),
        heading(s["treatment_title"], "h2", "jr-h2"),
        text('<div class="jr-steps">' + "".join(
            f'<div class="jr-step"><h3>{t}</h3><p>{d}</p></div>' for t, d in s["treatment"]) + "</div>"),
        heading(s["aftercare_title"], "h2", "jr-h2"),
        text(s["aftercare"], "jr-prose"),
    ]
    for mt, mh in s.get("more", []):
        left += [heading(mt, "h2", "jr-h2"), text(mh, "jr-prose")]
    if s.get("urgent"):
        left.append(text(f"<p><strong>When to seek urgent care:</strong> {s['urgent']}</p>", "jr-note"))
    left += [
        heading("Why choose Dr. Jawaria Rasheed in Bahawalpur?", "h2", "jr-h2"),
        text(f"<p>{s['why']}</p>", "jr-prose"),
    ]

    rel_links = "".join(f'<li><a href="/services/{r["slug"]}/">{r["title"]}</a></li>' for r in related)
    right = [
        heading("Book a consultation", "h3"),
        text("<p>See <strong>Dr. Jawaria Rasheed</strong>, Consultant Gynecologist &amp; IUI Specialist, in Bahawalpur.</p>"),
        button("Book an appointment", "/book-appointment/", "jr-btn-primary"),
        shortcode('[jr_cta_buttons book="0"]'),
        heading("Related services", "h3"),
        text(f'<ul class="jr-related-links">{rel_links}</ul>'),
    ]

    secs = [
        page_hero("Services · Bahawalpur", s["title"], s["intro"]),
        section([column(left, 64), column(right, 36, "jr-card jr-sticky")], "jr-section"),
        section([
            heading("Frequently asked questions", "h2", "jr-h2"),
            toggle(s["faqs"]),
        ], "jr-section jr-section--tint"),
    ]
    if related:
        cards = "".join(
            f'<a class="jr-service-card" href="/services/{r["slug"]}/"><span class="jr-service-card__icon" aria-hidden="true">{r["icon"]}</span>'
            f'<h3 class="jr-service-card__title">{r["title"]}</h3><p class="jr-service-card__text">{r["excerpt"]}</p>'
            '<span class="jr-service-card__more">Learn more &rarr;</span></a>' for r in related)
        secs.append(section([
            heading("You may also want to read", "h2", "jr-h2"),
            text(f'<div class="jr-services-grid">{cards}</div>'),
        ], "jr-section"))
    secs.append(section([
        text("<p><em>This page is general information and does not replace a consultation. Your treatment plan depends on your individual examination and test results. Content reviewed by Dr. Jawaria Rasheed.</em></p>", "jr-small"),
    ], "jr-section jr-section--tight"))
    secs.append(cta_band())
    return secs


def contact():
    return [
        page_hero("Contact", "Contact the clinic", "Call, WhatsApp or visit us. For appointments, you can also send a request online.", buttons=False),
        section([
            column([
                heading("Clinic details", "h2", "jr-h2"),
                shortcode("[jr_clinic_info]"),
                shortcode("[jr_cta_buttons]"),
            ], 50, "jr-card"),
            column([
                heading("Timings", "h2", "jr-h2"),
                shortcode("[jr_hours]"),
                text("<p class=\"jr-small\">Timings may change on public holidays. Please call or WhatsApp to confirm before visiting.</p>"),
            ], 50, "jr-card"),
        ], "jr-section"),
        section([shortcode("[jr_map]")], "jr-section jr-section--tight"),
        section([
            heading("Areas we serve", "h2", "jr-h2"),
            text("<p>Dr. Jawaria Rasheed sees patients from Bahawalpur and across South Punjab.</p>"),
            shortcode("[jr_areas]"),
        ], "jr-section jr-section--tint"),
        section([
            text("<p><strong>Emergency?</strong> This clinic does not provide emergency care. If you have heavy bleeding, severe pain or another emergency, please go to the nearest hospital emergency department.</p>", "jr-note"),
        ], "jr-section jr-section--tight"),
    ]


def book():
    return [
        page_hero("Appointments", "Book an appointment",
                  "Fill in the short form below and our team will call or WhatsApp you to confirm the date and time.", buttons=False),
        section([
            column([shortcode("[jr_appointment_form]")], 62),
            column([
                heading("Prefer to talk?", "h3"),
                text("<p>You can also call or message the clinic directly.</p>"),
                shortcode('[jr_cta_buttons book="0"]'),
                heading("Clinic timings", "h3"),
                shortcode("[jr_hours]"),
                heading("What to bring", "h3"),
                checklist([
                    "Previous reports and ultrasound scans",
                    "A list of medicines you take",
                    "For fertility visits: both partners, if possible",
                ]),
            ], 38, "jr-card"),
        ], "jr-section"),
    ]


def faqs():
    pairs = [
        ("How do I book an appointment?", "<p>Use the <a href=\"/book-appointment/\">online request form</a>, call the clinic or send a WhatsApp message. The clinic will confirm your date and time.</p>"),
        ("Is my appointment confirmed when I submit the form?", "<p>Not yet. The form sends a request. Your appointment is confirmed when the clinic contacts you.</p>"),
        ("Can my husband or a family member come with me?", "<p>Yes. For fertility consultations it is especially helpful if both partners attend.</p>"),
        ("What should I bring to my first visit?", "<p>Previous reports and scans, a list of your medicines and the date of your last period.</p>"),
        ("Is IUI the same as IVF?", "<p>No. In IUI, prepared sperm are placed in the uterus and fertilisation happens naturally inside the body. In IVF, eggs are collected and fertilised in a laboratory. IUI is simpler and less invasive. IVF may be advised in other situations.</p>"),
        ("Are consultations private?", "<p>Yes. Your concerns are discussed confidentially, and examinations are done only with your consent.</p>"),
        ("Do you provide emergency care?", "<p>No. For heavy bleeding, severe pain or other emergencies, please go to the nearest hospital emergency department.</p>"),
    ]
    return [
        page_hero("Patient guide", "Frequently asked questions", "Answers to common questions about appointments and care.", buttons=False),
        section([toggle(pairs)], "jr-section jr-narrow-section"),
        cta_band(),
    ]


def legal(title, body):
    return [
        page_hero("Legal", title, "Last updated: please add the date when finalised.", buttons=False),
        section([text(body, "jr-prose jr-narrow")], "jr-section"),
    ]


PRIVACY = """
<p>This policy explains what information this website collects and how it is used. <strong>Draft — please have it reviewed before publishing.</strong></p>
<h2>What we collect</h2>
<ul>
<li><strong>Appointment requests:</strong> name, mobile number, reason for visit, preferred date and time, and any short note you add.</li>
<li><strong>Messages you send us</strong> by phone, WhatsApp or email.</li>
<li><strong>Basic technical data</strong> such as browser type and pages visited, if analytics is enabled.</li>
</ul>
<h2>How we use it</h2>
<ul>
<li>To contact you about your appointment request.</li>
<li>To run and improve the website.</li>
</ul>
<p>We do not sell your information. We do not ask for detailed medical history through this website.</p>
<h2>Who can see it</h2>
<p>Only authorised clinic staff can see appointment requests. Requests are stored securely and deleted when they are no longer needed for the purpose they were collected for.</p>
<h2>Your choices</h2>
<p>You can ask us to correct or delete your information by contacting the clinic using the details on the Contact page.</p>
<h2>Cookies</h2>
<p>This website may use essential cookies to work properly and, if enabled, analytics cookies to understand how it is used.</p>
"""

DISCLAIMER = """
<p>The information on this website is for general education only. It is <strong>not</strong> a substitute for professional medical advice, diagnosis or treatment.</p>
<ul>
<li>Always seek the advice of a qualified doctor about any medical condition.</li>
<li>Do not ignore professional advice or delay seeking it because of something you read on this website.</li>
<li>Treatment results vary from person to person. No outcome is guaranteed.</li>
<li>In an emergency, go to the nearest hospital emergency department.</li>
</ul>
<p>Submitting an appointment request does not create a doctor–patient relationship until your appointment is confirmed and you are seen by the doctor.</p>
"""


# ------------------------------------------------------------------ assemble

def build_pages():
    pages = []

    def add(slug, title, secs, parent="", order=0, excerpt="", icon="", seo_title="", seo_desc=""):
        pages.append({
            "slug": slug, "title": title, "parent": parent, "order": order, "excerpt": excerpt,
            "icon": icon, "seo_title": seo_title, "seo_description": seo_desc,
            "elementor": secs, "fallback": fallback_text(secs),
        })

    home_secs, _ = home()
    add("home", "Home", home_secs, seo_title="Dr. Jawaria Rasheed | Consultant Gynecologist & IUI Specialist",
        seo_desc="Dr. Jawaria Rasheed is a consultant gynecologist with 15+ years’ experience in women’s health, fertility evaluation and IUI. Book an appointment online or on WhatsApp.")
    add("about", "About Dr. Jawaria Rasheed", about(), order=1,
        seo_desc="About Dr. Jawaria Rasheed — consultant gynecologist and IUI specialist with over 15 years of experience in women’s health and fertility care.")
    add("services", "Services", services_hub(), order=2,
        seo_desc="Gynecology, pregnancy care, infertility evaluation, IUI, PCOS, menopause and more with Dr. Jawaria Rasheed.")
    for i, s in enumerate(SERVICES):
        add(s["slug"], s["title"], service_page(s), parent="services", order=i, excerpt=s["excerpt"], icon=s["icon"],
            seo_title=s["seo_title"],
            seo_desc=s["excerpt"] + " Consultant gynecologist Dr. Jawaria Rasheed in Bahawalpur.")
    add("faqs", "FAQs", faqs(), order=3, seo_desc="Answers to common questions about appointments, IUI and women’s health care with Dr. Jawaria Rasheed.")
    add("reviews", "Patient Reviews", reviews_page(), order=3, seo_desc="Patient reviews for Dr. Jawaria Rasheed, consultant gynecologist and IUI specialist.")
    add("videos", "Videos", videos_page(), order=3, seo_desc="Health videos by Dr. Jawaria Rasheed on women’s health, fertility, IUI and pregnancy.")
    add("contact", "Contact", contact(), order=4, seo_desc="Clinic address, phone, WhatsApp and timings for Dr. Jawaria Rasheed.")
    add("book-appointment", "Book an Appointment", book(), order=5, seo_desc="Request an appointment with Dr. Jawaria Rasheed online. The clinic will call or WhatsApp you to confirm.")
    add("privacy-policy", "Privacy Policy", legal("Privacy policy", PRIVACY), order=8)
    add("medical-disclaimer", "Medical Disclaimer", legal("Medical disclaimer", DISCLAIMER), order=9)
    # Blog page: no Elementor layout — WordPress lists posts on it.
    pages.append({"slug": "blog", "title": "Blog", "parent": "", "order": 6, "excerpt": "", "icon": "",
                  "seo_title": "", "seo_description": "Health articles by Dr. Jawaria Rasheed on women’s health, fertility, pregnancy and gynecology.",
                  "elementor": [], "fallback": ""})
    return pages


# ------------------------------------------------------------------ preview

SAMPLE = {
    "doctor_name": "Dr. Jawaria Rasheed", "experience": "15", "qualifications": "MBBS, FCPS (Obstetrics &amp; Gynaecology) — sample, replace",
    "phone": "0300 0000000", "address": "Clinic address, Bahawalpur (sample)", "hours": [("Monday – Saturday", "5:00 pm – 9:00 pm (sample)"), ("Sunday", "Closed")],
}


def fake_shortcode(name, args, pages):
    s = SAMPLE
    btn = lambda c, t: f'<a class="jr-btn {c}" href="#">{t}</a>'
    if name == "jr_experience":
        return s["experience"]
    if name == "jr_qualifications":
        return s["qualifications"]
    if name == "jr_cta_buttons":
        light = 'style="light"' in args
        center = ' jr-center' if 'align="center"' in args else ''
        out = f'<div class="jr-btn-row{center}">'
        if 'book="0"' not in args:
            out += btn("jr-btn--light" if light else "jr-btn--primary", "Book an appointment")
        out += btn("jr-btn--whatsapp", "WhatsApp")
        out += btn("jr-btn--light" if light else "jr-btn--ghost", f"Call {s['phone']}")
        return out + "</div>"
    if name == "jr_stats":
        rows = [("15+", "Years of experience"), ("IUI", "Fertility treatment offered"), ("PCOS", "Specialised care"), ("1:1", "Private consultations")]
        return '<div class="jr-stats">' + "".join(f'<div class="jr-stat"><span class="jr-stat__num">{a}</span><span class="jr-stat__label">{b}</span></div>' for a, b in rows) + '</div>'
    if name == "jr_services_grid":
        limit = 6 if 'limit="6"' in args else 99
        out = '<div class="jr-services-grid">'
        for sv in SERVICES[:limit]:
            out += (f'<a class="jr-service-card" href="{sv["slug"]}.html"><span class="jr-service-card__icon">{sv["icon"]}</span>'
                    f'<h3 class="jr-service-card__title">{sv["title"]}</h3><p class="jr-service-card__text">{sv["excerpt"]}</p>'
                    '<span class="jr-service-card__more">Learn more &rarr;</span></a>')
        return out + "</div>"
    if name == "jr_latest_posts":
        out = '<div class="jr-posts-grid">'
        for p in POSTS[:3]:
            out += f'<article class="jr-post-card"><div class="jr-post-card__body"><h3 class="jr-post-card__title"><a href="#">{p["title"]}</a></h3><p class="jr-post-card__meta">Draft article</p><p>{p["excerpt"]}</p></div></article>'
        return out + "</div>"
    if name == "jr_clinic_info":
        return (f'<ul class="jr-info"><li><strong>Address</strong>{s["address"]}<br><a href="#">Get directions</a></li>'
                f'<li><strong>Phone</strong><a href="#">{s["phone"]}</a></li><li><strong>WhatsApp</strong><a href="#">{s["phone"]}</a></li>'
                '<li><strong>Timings</strong>Mon – Sat, by appointment</li></ul>')
    if name == "jr_hours":
        return '<table class="jr-hours"><tbody>' + "".join(f"<tr><td>{a}</td><td>{b}</td></tr>" for a, b in s["hours"]) + "</tbody></table>"
    if name == "jr_areas":
        return '<ul class="jr-areas">' + "".join(f"<li>{c}</li>" for c in ["Bahawalpur", "Multan", "Rahim Yar Khan", "Bahawalnagar", "Lodhran", "Vehari", "Ahmedpur East", "Hasilpur", "Khanpur", "Liaquatpur"]) + "</ul>"
    if name == "jr_rating":
        return '<span class="jr-rating jr-pv-note">★★★★★ Google rating shows here once added in Clinic Info</span>'
    if name == "jr_reviews":
        return '<div class="jr-reviews">' + "".join('<figure class="jr-review jr-review--empty"><span class="jr-review__stars">★★★★★</span><p class="jr-review__head">Real patient review</p><blockquote class="jr-review__text"><p>Reviews are added under Patient Reviews, only with the patient’s permission.</p></blockquote><figcaption class="jr-review__name">Patient name</figcaption></figure>' for _ in range(3)) + '</div>'
    if name == "jr_videos":
        return '<div class="jr-videos">' + "".join(f'<a class="jr-video" href="#"><span class="jr-video__thumb jr-video__thumb--empty"><span class="jr-video__play">▶</span></span><span class="jr-video__title">YouTube video {i} (add link in Clinic Info)</span></a>' for i in (1, 2, 3)) + '</div><p class="jr-center"><a class="jr-btn jr-btn--ghost" href="#">Visit our YouTube channel</a></p>'
    if name == "jr_map":
        return '<div class="jr-map"><div style="height:280px;border-radius:14px;background:#e3eeee;display:grid;place-items:center;color:#5b6b70">Google Map appears here once the embed link is added in Clinic Info</div></div>'
    if name == "jr_appointment_form":
        opts = "".join(f"<option>{x}</option>" for x in ["General gynecology consultation"] + [sv["title"] for sv in SERVICES] + ["Other / not sure"])
        return f'''<form class="jr-form" onsubmit="return false"><div class="jr-form__grid">
<div><label>Patient name <span class="jr-req">*</span></label><input type="text"></div>
<div><label>Mobile / WhatsApp number <span class="jr-req">*</span></label><input type="tel" placeholder="03xx xxxxxxx"></div>
<div class="jr-form__full"><label>Reason for visit</label><select>{opts}</select></div>
<div><label>Preferred date <span class="jr-req">*</span></label><input type="date"></div>
<div><label>Preferred time</label><select><option>Any time</option><option>Morning</option><option>Afternoon</option><option>Evening</option></select></div>
<div class="jr-form__full"><label>Anything we should know to arrange the visit? (optional)</label><textarea></textarea><p class="jr-form__hint">e.g. first visit or follow-up, best time to call. Please do not share medical details here — the doctor will discuss them with you in private.</p></div>
<div class="jr-form__full"><label class="jr-form__consent"><input type="checkbox"><span>I agree that the clinic may contact me by phone or WhatsApp about this appointment. <span class="jr-req">*</span></span></label></div>
<div class="jr-form__full"><button type="submit" class="jr-btn jr-btn--primary">Send appointment request</button><p class="jr-form__hint">Your appointment is confirmed only after the clinic contacts you.</p></div>
</div></form>'''
    return ""


# Menus for the preview — mirror jr_menu_specs() in includes/setup.php.
_T = {sv["slug"]: sv["title"] for sv in SERVICES}
_T.update({"about": "About Dr. Jawaria", "reviews": "Patient Reviews", "blog": "Health Articles", "videos": "Videos",
           "faqs": "FAQs", "contact": "Contact Us", "book-appointment": "Book Appointment", "services": "Services"})


def P(slug, title=None, cls="", children=None):
    return (slug + ".html", title or _T.get(slug, slug), cls, children or [])


def H(title, children):
    return ("#", title, "jr-col-head", children)


MENU_TOP = [P("about"), ("#", "Patient Guide", "", [P("reviews"), P("videos"), P("faqs"), P("book-appointment")]), ("blog.html", "Blog", "", []), P("contact")]
MENU_SERVICES = [
    P("services", "Gynecology", "", [P("gynecology-consultation"), P("menstrual-problems"), P("cervical-screening"), P("family-planning"), P("menopause")]),
    P("infertility-evaluation", "Fertility & IUI", "jr-mega", [
        H("Fertility Assessment", [P("infertility-evaluation"), P("ovulation-induction", "Follicle Monitoring")]),
        H("Fertility Treatment", [P("iui-treatment"), P("ovulation-induction", "Ovulation Induction")]),
        H("Related Conditions", [P("pcos"), P("endometriosis"), P("fibroids")]),
    ]),
    P("pregnancy-care", "Pregnancy Care"), P("pcos", "PCOS"), P("endometriosis", "Endometriosis"),
    P("fibroids", "Fibroids"), P("menopause", "Menopause"), P("about", "Dr. Jawaria Rasheed", "jr-menu-doctor"),
]
MENU_FOOT_SERVICES = [P("iui-treatment", "IUI Treatment"), P("infertility-evaluation"), P("pcos"), P("pregnancy-care"), P("endometriosis"), P("fibroids"), P("menopause"), P("cervical-screening")]
MENU_FOOT_LINKS = [P("about"), ("blog.html", "Blog", "", []), P("reviews"), P("videos"), P("faqs"), P("contact"), P("book-appointment")]


def menu_html(items, cls="jr-menu", sub=False):
    out = f'<ul class="{"sub-menu" if sub else cls}">'
    for href, title, c, kids in items:
        classes = "menu-item " + c + (" menu-item-has-children" if kids else "")
        out += f'<li class="{classes.strip()}"><a href="{href}">{title.replace("&", "&amp;")}</a>'
        if kids:
            out += menu_html(kids, sub=True)
        out += "</li>"
    return out + "</ul>"


PREVIEW_SHELL = """<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>{title} — Preview</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"><link rel="stylesheet" href="preview-base.css"><link rel="stylesheet" href="../wp-content/themes/jawaria-child/assets/css/site.css">
</head><body>
<div class="jr-pv-banner">Design preview — the real site is built in WordPress. Sample contact details shown.</div>
<div class="jr-topbar"><div class="jr-topbar__inner"><span class="jr-topbar__item">Bahawalpur · Mon – Sat, by appointment</span><span class="jr-topbar__links"><a href="#">&#9742; Call 0300 0000000</a><a href="#">WhatsApp 0300 0000000</a></span></div></div>
<header class="jr-header" data-jr-header>
 <div class="jr-header__main">
  <div class="jr-header__brand"><a class="jr-logo" href="index.html"><span class="jr-logo__mark">JR</span><span class="jr-logo__text"><span class="jr-logo__name">Dr. Jawaria Rasheed</span><span class="jr-logo__tag">Consultant Gynecologist &amp; IUI Specialist</span></span></a></div>
  <nav class="jr-nav jr-nav--top">{menu_top}</nav>
  <div class="jr-header__actions"><a class="jr-header__call" href="#"><span class="jr-header__call-label">Call us</span><span class="jr-header__call-num">0300 0000000</span></a><a class="jr-btn jr-btn--primary jr-btn--sm" href="book-appointment.html">Book<span class="jr-hide-sm">An Appointment</span></a><button class="jr-burger" type="button" aria-expanded="false" data-jr-burger><span></span><span></span><span></span></button></div>
 </div>
 <div class="jr-header__services"><nav class="jr-nav jr-nav--services">{menu_services}</nav></div>
</header>
<main>{body}</main>
<footer class="jr-footer"><div class="jr-footer__grid">
 <div class="jr-footer__col jr-footer__brand"><p class="jr-footer__name">Dr. Jawaria Rasheed</p><p class="jr-footer__tag">Consultant Gynecologist &amp; IUI Specialist</p><p class="jr-footer__about">Compassionate, personalised care in gynecology, women’s health and fertility, including IUI — at every stage of life.</p><div class="jr-social"><a class="jr-social__link jr-social__link--facebook" href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a><a class="jr-social__link jr-social__link--instagram" href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a><a class="jr-social__link jr-social__link--youtube" href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a><a class="jr-social__link jr-social__link--tiktok" href="#" aria-label="TikTok"><i class="fab fa-tiktok"></i></a></div></div>
 <div class="jr-footer__col"><p class="jr-footer__heading">Our Services</p>{foot_services}</div>
 <div class="jr-footer__col"><p class="jr-footer__heading">Quick Links</p>{foot_links}</div>
 <div class="jr-footer__col"><p class="jr-footer__heading">Get In Touch</p>{info}<p><a class="jr-btn jr-btn--light jr-btn--sm" href="book-appointment.html">Book Online</a></p></div>
</div>
<div class="jr-footer__legal"><p class="jr-footer__note">Information on this website is for general education and does not replace a consultation. In an emergency, go to the nearest hospital emergency department.</p>
<div class="jr-footer__bottom"><span>&copy; 2026 Dr. Jawaria Rasheed. All rights reserved.</span><span class="jr-footer__policies"><a href="privacy-policy.html">Privacy Policy</a><a href="medical-disclaimer.html">Medical Disclaimer</a><a href="#">Sitemap</a></span></div></div></footer>
<div class="jr-floats"><a class="jr-float jr-float--call" href="#"><span>&#9742;</span></a><a class="jr-float jr-float--wa" href="#"><svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2z"/></svg></a></div>
<script src="../wp-content/themes/jawaria-child/assets/js/nav.js"></script>
</body></html>"""


def write_preview(pages):
    out_dir = os.path.join(ROOT, "preview")
    os.makedirs(out_dir, exist_ok=True)
    media = {"doctor": "../wp-content/plugins/jawaria-clinic/assets/images/doctor-placeholder.jpg",
             "clinic": "../wp-content/plugins/jawaria-clinic/assets/images/clinic-placeholder.jpg"}
    sc = lambda n, a: fake_shortcode(n, a, pages)
    for p in pages:
        if not p["elementor"]:
            continue
        name = "index" if p["slug"] == "home" else p["slug"]
        body = render_preview(p["elementor"], sc, media)
        body = re.sub(r'href="/(?:services/)?([a-z-]+)/"', lambda m: f'href="{m.group(1)}.html"', body)
        html_doc = PREVIEW_SHELL.format(title=p["title"], body=body, info=sc("jr_clinic_info", ""), menu_top=menu_html(MENU_TOP), menu_services=menu_html(MENU_SERVICES), foot_services=menu_html(MENU_FOOT_SERVICES, "jr-footer__links"), foot_links=menu_html(MENU_FOOT_LINKS, "jr-footer__links"))
        with open(os.path.join(out_dir, name + ".html"), "w", encoding="utf-8") as f:
            f.write(html_doc)


def write_blog_preview():
    cards = "".join(
        f'<article class="jr-post-card"><div class="jr-post-card__body"><h3 class="jr-post-card__title"><a href="#">{p["title"]}</a></h3>'
        f'<p class="jr-post-card__meta">{", ".join(p["categories"])} · Draft</p><p>{p["excerpt"]}</p></div></article>' for p in POSTS)
    body = (f'<section class="elementor-section jr-page-hero"><div class="elementor-container"><div class="elementor-column" style="--pv-w:100%"><div class="elementor-widget-wrap">'
            f'<div class="elementor-widget jr-eyebrow"><div class="elementor-widget-container"><p class="elementor-heading-title">Blog</p></div></div>'
            f'<div class="elementor-widget"><div class="elementor-widget-container"><h1 class="elementor-heading-title">Health articles</h1></div></div></div></div></div></section>'
            f'<section class="elementor-section jr-section"><div class="elementor-container"><div class="elementor-column" style="--pv-w:100%"><div class="elementor-widget-wrap"><div class="jr-posts-grid">{cards}</div></div></div></div></section>')
    sc = lambda n, a: fake_shortcode(n, a, [])
    html_doc = PREVIEW_SHELL.format(title="Blog", body=body, info=sc("jr_clinic_info", ""), menu_top=menu_html(MENU_TOP), menu_services=menu_html(MENU_SERVICES), foot_services=menu_html(MENU_FOOT_SERVICES, "jr-footer__links"), foot_links=menu_html(MENU_FOOT_LINKS, "jr-footer__links"))
    with open(os.path.join(ROOT, "preview", "blog.html"), "w", encoding="utf-8") as f:
        f.write(html_doc)


def main():
    pages = build_pages()
    os.makedirs(os.path.join(PLUGIN, "content"), exist_ok=True)
    with open(os.path.join(PLUGIN, "content", "pages.json"), "w", encoding="utf-8") as f:
        json.dump(pages, f, ensure_ascii=False, indent=1)
    with open(os.path.join(PLUGIN, "content", "posts.json"), "w", encoding="utf-8") as f:
        json.dump(POSTS, f, ensure_ascii=False, indent=1)
    write_preview(pages)
    write_blog_preview()
    print(f"{len(pages)} pages, {len(POSTS)} posts written.")


if __name__ == "__main__":
    main()
