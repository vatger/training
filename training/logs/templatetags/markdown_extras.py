from django import template
import markdown
import bleach

register = template.Library()


@register.filter
def markdownify(text):
    raw_html = markdown.markdown(text, extensions=["extra", "nl2br"])

    allowed_tags = list(bleach.sanitizer.ALLOWED_TAGS) + [
        "p",
        "pre",
        "code",
        "br",
        "img",
        "a",
    ]
    allowed_attrs = {
        **bleach.sanitizer.ALLOWED_ATTRIBUTES,
        "img": ["src", "alt", "title"],
        "a": ["href", "title"],  # still allow links in case someone uses normal links
    }

    clean_html = bleach.clean(raw_html, tags=allowed_tags, attributes=allowed_attrs)
    return clean_html
