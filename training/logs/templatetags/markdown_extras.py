from django import template
import markdown
import bleach
from bleach.sanitizer import ALLOWED_TAGS

register = template.Library()


@register.filter
def markdownify(text):
    """
    Convert markdown text to HTML.
    This version supports the extended syntax from EasyMDE.
    """
    if not text:
        return ""
        
    # Add extensions to support GitHub-Flavored Markdown and other EasyMDE features
    extensions = [
        "extra",           # includes tables, footnotes, etc.
        "nl2br",           # newlines become <br> tags
        "sane_lists",      # better list handling
        "smarty",          # smart quotes, dashes, etc.
        "codehilite",      # syntax highlighting
        "fenced_code",     # code blocks with ```
    ]
    
    # Convert markdown to HTML
    raw_html = markdown.markdown(text, extensions=extensions)

    # Define allowed HTML tags and attributes
    allowed_tags = list(ALLOWED_TAGS) + [
        'p', 'pre', 'code', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'hr', 'br', 'img', 'a', 'table', 'thead', 'tbody', 'tr', 'th', 
        'td', 'ul', 'ol', 'li', 'dl', 'dt', 'dd', 'blockquote', 'strong',
        'em', 'span', 'div', 'del', 'ins', 'sup', 'sub'
    ]
    
    allowed_attrs = {
        **bleach.sanitizer.ALLOWED_ATTRIBUTES,
        'img': ['src', 'alt', 'title', 'width', 'height', 'class'],
        'a': ['href', 'title', 'name', 'id', 'target', 'rel', 'class'],
        'code': ['class'],
        'pre': ['class'],
        'span': ['class', 'style'],
        'div': ['class'],
        'table': ['class', 'width'],
        'th': ['scope', 'colspan', 'rowspan'],
        'td': ['colspan', 'rowspan'],
        '*': ['id', 'class'],  # Allow id and class on all elements
    }

    # Clean HTML to prevent XSS
    clean_html = bleach.clean(
        raw_html, 
        tags=allowed_tags, 
        attributes=allowed_attrs,
        strip=True
    )
    
    # Format the HTML (optional)
    clean_html = clean_html.replace('<table>', '<table class="table table-bordered">')
    
    return clean_html