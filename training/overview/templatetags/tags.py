from django import template

register = template.Library()


@register.filter
def format_date(value):
    """
    Custom filter to format a date object as DD/MM/YY.
    """
    if not value:
        return ""
    return value.strftime("%d.%m.%y")
