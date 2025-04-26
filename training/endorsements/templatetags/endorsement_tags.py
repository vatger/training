from django import template
import math

register = template.Library()

@register.filter
def get_item(dictionary, key):
    """
    Custom template filter to access dictionary values by key.
    
    Usage:
    {{ dictionary|get_item:key }}
    """
    if dictionary is None:
        return None
    return dictionary.get(key)

@register.filter
def get_group_count(endorsements_dict, group_name):
    """
    Returns the count of endorsements for a specific group.
    
    Usage:
    {{ endorsements|get_group_count:group_name }}
    """
    if not endorsements_dict or group_name not in endorsements_dict:
        return 0
    return len(endorsements_dict[group_name])

@register.filter
def divide(value, arg):
    """
    Divides the value by the argument.
    
    Usage:
    {{ value|divide:arg }}
    """
    try:
        return float(value) / float(arg)
    except (ValueError, ZeroDivisionError):
        return 0

@register.filter
def widthpercent(value, max_value, cap_at_100=True):
    """
    Calculate width percentage for progress bars.
    
    Usage:
    style="width: {% widthpercent activity min_hours_required %}"
    """
    try:
        percentage = (float(value) / float(max_value)) * 100
        if cap_at_100 and percentage > 100:
            return "100%"
        return f"{percentage:.0f}%"
    except (ValueError, ZeroDivisionError):
        return "0%"