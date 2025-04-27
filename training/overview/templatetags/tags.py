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
def split_solo_data(solo_string, part):
    """
    Parses the solo data string in format "days/delta" and returns the requested part.
    
    Usage:
    {{ solo_string|split_solo_data:"remaining" }}  # Returns remaining days
    {{ solo_string|split_solo_data:"delta" }}      # Returns delta days
    """
    try:
        if solo_string == "Add Solo":
            return 0
            
        parts = solo_string.split('/')
        remaining_days = int(parts[0])
        delta_days = int(parts[1])
        
        if part == "remaining":
            return remaining_days
        elif part == "delta":
            return delta_days
        else:
            return 0
    except (ValueError, IndexError):
        return 0

@register.filter
def count_passing_logs(logs):
    """
    Counts the number of passing logs in a queryset.
    
    Usage:
    {{ logs|count_passing_logs }}
    """
    return sum(1 for log in logs if log.result)

@register.filter
def calculate_progress(logs):
    """
    Calculates the training progress percentage based on log results.
    The calculation is weighted toward more recent sessions.
    
    Usage:
    {{ logs|calculate_progress }}
    """
    if not logs:
        return 0
        
    # More weight to recent sessions, diminishing weight to older ones
    total_weight = 0
    weighted_sum = 0
    
    # Sort logs by date, most recent first
    sorted_logs = sorted(logs, key=lambda log: log.session_date, reverse=True)
    
    for i, log in enumerate(sorted_logs):
        # Weight decreases exponentially with age
        weight = 1.0 / (1.0 + i * 0.5)
        total_weight += weight
        
        # Add weighted value (1 for pass, 0 for fail)
        if log.result:
            weighted_sum += weight
        
    # Calculate percentage
    if total_weight > 0:
        progress = (weighted_sum / total_weight) * 100
    else:
        progress = 0
        
    # Ensure progress is between 0-100 and rounded to nearest integer
    return min(100, max(0, round(progress)))

@register.filter
def log_count(active_dict, inactive_dict):
    count = 0
    
    # Count logs in active courses
    for logs in active_dict.values():
        count += len(logs)
        
    # Count logs in inactive courses
    for logs in inactive_dict.values():
        count += len(logs)
        
    return count