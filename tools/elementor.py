"""Tiny builder for Elementor page JSON (classic section/column layout) plus a
static HTML preview renderer that mimics Elementor's markup so the child
theme's CSS can be checked without running WordPress."""

import hashlib
import html
import itertools
import re

_counter = itertools.count()


def eid(seed=""):
    n = next(_counter)
    return hashlib.md5(f"{seed}-{n}".encode()).hexdigest()[:7]


# ---------------------------------------------------------------- widgets

def heading(text, tag="h2", cls="", align=""):
    s = {"title": text, "header_size": tag}
    if cls:
        s["_css_classes"] = cls
    if align:
        s["align"] = align
    return {"id": eid(text), "elType": "widget", "widgetType": "heading", "settings": s, "elements": []}


def text(html_str, cls=""):
    s = {"editor": html_str}
    if cls:
        s["_css_classes"] = cls
    return {"id": eid(html_str[:20]), "elType": "widget", "widgetType": "text-editor", "settings": s, "elements": []}


def button(label, url, cls="jr-btn-primary", align=""):
    s = {"text": label, "link": {"url": url, "is_external": "", "nofollow": "", "custom_attributes": ""},
         "_css_classes": cls}
    if align:
        s["align"] = align
    return {"id": eid(label), "elType": "widget", "widgetType": "button", "settings": s, "elements": []}


def image(key, alt="", cls="jr-photo"):
    s = {"image": {"url": "{{img:%s:url}}" % key, "id": "{{img:%s:id}}" % key, "alt": alt, "source": "library"},
         "image_size": "large", "_css_classes": cls}
    return {"id": eid(key), "elType": "widget", "widgetType": "image", "settings": s, "elements": []}


def shortcode(code, cls=""):
    s = {"shortcode": code}
    if cls:
        s["_css_classes"] = cls
    return {"id": eid(code), "elType": "widget", "widgetType": "shortcode", "settings": s, "elements": []}


def checklist(items, cls="jr-checks"):
    s = {
        "icon_list": [
            {"_id": eid(t), "text": t, "selected_icon": {"value": "fas fa-check-circle", "library": "fa-solid"}}
            for t in items
        ],
        "_css_classes": cls,
    }
    return {"id": eid("list"), "elType": "widget", "widgetType": "icon-list", "settings": s, "elements": []}


def toggle(pairs, cls="jr-faq"):
    s = {
        "tabs": [{"_id": eid(q), "tab_title": q, "tab_content": a} for q, a in pairs],
        "_css_classes": cls,
    }
    return {"id": eid("faq"), "elType": "widget", "widgetType": "toggle", "settings": s, "elements": []}


def spacer(px=16):
    return {"id": eid("sp"), "elType": "widget", "widgetType": "spacer",
            "settings": {"space": {"unit": "px", "size": px}}, "elements": []}


# ---------------------------------------------------------------- layout

def column(widgets, size=100, cls=""):
    s = {"_column_size": size, "_inline_size": None}
    if cls:
        s["css_classes"] = cls
    return {"id": eid("col"), "elType": "column", "settings": s, "elements": widgets, "isInner": False}


def section(columns, cls="jr-section", middle=False, full=False):
    if columns and columns[0].get("elType") == "widget":
        columns = [column(columns)]
    s = {"css_classes": cls, "gap": "extended"}
    if full:
        s.update({"layout": "full_width", "gap": "no"})
    if middle:
        s["content_position"] = "middle"
    return {"id": eid("sec"), "elType": "section", "settings": s, "elements": columns, "isInner": False}


def fallback_text(sections):
    """Plain HTML copy of the page for post_content (search engines / no-Elementor fallback)."""
    out = []

    def walk(el):
        if el["elType"] == "widget":
            s = el["settings"]
            w = el["widgetType"]
            if w == "heading":
                out.append(f"<{s['header_size']}>{html.escape(s['title'])}</{s['header_size']}>")
            elif w == "text-editor":
                out.append(re.sub(r"\[[^\]]+\]", "", s["editor"]))
            elif w == "icon-list":
                out.append("<ul>" + "".join(f"<li>{html.escape(i['text'])}</li>" for i in s["icon_list"]) + "</ul>")
            elif w == "toggle":
                for t in s["tabs"]:
                    out.append(f"<h3>{html.escape(t['tab_title'])}</h3>{t['tab_content']}")
        for c in el.get("elements", []):
            walk(c)

    for sec in sections:
        walk(sec)
    return "\n".join(out)


# ---------------------------------------------------------------- preview

def render_preview(sections, shortcode_fn, media):
    def r(el):
        t = el["elType"]
        s = el["settings"]
        if t == "section":
            inner = "".join(r(c) for c in el["elements"])
            mid = " jr-pv-middle" if s.get("content_position") == "middle" else ""
            return f'<section class="elementor-section elementor-top-section {s.get("css_classes","")}{mid}"><div class="elementor-container">{inner}</div></section>'
        if t == "column":
            inner = "".join(r(c) for c in el["elements"])
            size = s.get("_column_size", 100)
            return f'<div class="elementor-column elementor-col-{size} {s.get("css_classes","")}" style="--pv-w:{size}%"><div class="elementor-widget-wrap">{inner}</div></div>'
        w = el["widgetType"]
        cls = s.get("_css_classes", "")
        if w == "heading":
            align = f' style="text-align:{s["align"]}"' if s.get("align") else ""
            body = f'<{s["header_size"]} class="elementor-heading-title"{align}>{html.escape(s["title"])}</{s["header_size"]}>'
        elif w == "text-editor":
            body = re.sub(r"\[(jr_[a-z_]+)([^\]]*)\]", lambda m: shortcode_fn(m.group(1), m.group(2)), s["editor"])
        elif w == "button":
            al = f' style="text-align:{s["align"]}"' if s.get("align") else ""
            body = f'<div class="elementor-button-wrapper"{al}><a class="elementor-button" href="{html.escape(s["link"]["url"])}">{html.escape(s["text"])}</a></div>'
        elif w == "image":
            key = s["image"]["url"].split(":")[1]
            body = f'<img src="{media[key]}" alt="{html.escape(s["image"].get("alt",""))}">'
        elif w == "shortcode":
            m = re.match(r"\[(jr_[a-z_]+)([^\]]*)\]", s["shortcode"])
            body = shortcode_fn(m.group(1), m.group(2))
        elif w == "icon-list":
            body = '<ul class="elementor-icon-list-items">' + "".join(
                f'<li class="elementor-icon-list-item"><span class="elementor-icon-list-icon">✓</span><span class="elementor-icon-list-text">{html.escape(i["text"])}</span></li>'
                for i in s["icon_list"]) + "</ul>"
        elif w == "toggle":
            body = "".join(f'<details class="elementor-toggle-item"><summary class="elementor-tab-title">{html.escape(t["tab_title"])}</summary><div class="elementor-tab-content">{t["tab_content"]}</div></details>' for t in s["tabs"])
        elif w == "spacer":
            body = f'<div style="height:{s["space"]["size"]}px"></div>'
        else:
            body = ""
        return f'<div class="elementor-element elementor-widget elementor-widget-{w} {cls}"><div class="elementor-widget-container">{body}</div></div>'

    return "".join(r(s) for s in sections)
