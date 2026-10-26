"""All service pages (order = menu/grid order). Edit the text in
content_services_a.py and content_services_b.py, then run tools/build.py."""

from content_services_a import SERVICES_A
from content_services_b import SERVICES_B

from content_services_more import MORE

SERVICES = SERVICES_A + SERVICES_B
for _s in SERVICES:
    _s["more"] = MORE.get(_s["slug"], [])
