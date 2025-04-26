from django import template
from django.urls import reverse, NoReverseMatch

register = template.Library()

@register.simple_tag(takes_context=True)
def breadcrumb_for(context, model_instance=None, title=None):
    """
    Generate a breadcrumb item dictionary for a specific model instance.
    
    Usage:
    {% load breadcrumb_tags %}
    {% breadcrumb_for form.course as course_crumb %}
    
    Then add to breadcrumbs:
    {% with breadcrumbs=breadcrumbs|add_to_list:course_crumb %}
    """
    result = {}
    
    if model_instance:
        # Attempt to get a title from the instance
        if hasattr(model_instance, 'name'):
            result['title'] = model_instance.name
        elif hasattr(model_instance, 'title'):
            result['title'] = model_instance.title
        elif hasattr(model_instance, '__str__'):
            result['title'] = str(model_instance)
            
        # Override with explicitly provided title if given
        if title:
            result['title'] = title
            
        # Try to get a URL
        if hasattr(model_instance, 'get_absolute_url'):
            result['url'] = model_instance.get_absolute_url()
        else:
            # Try common model-based URL patterns
            model_name = model_instance.__class__.__name__.lower()
            try:
                result['url'] = reverse(f'{model_name}_detail', args=[model_instance.pk])
            except NoReverseMatch:
                try:
                    app_name = model_instance._meta.app_label
                    result['url'] = reverse(f'{app_name}:{model_name}_detail', args=[model_instance.pk])
                except (NoReverseMatch, AttributeError):
                    result['url'] = '#'
                    
    return result

@register.filter
def add_to_list(value, arg):
    """
    Add an item to a list.
    
    Usage:
    {% with new_list=old_list|add_to_list:new_item %}
    """
    result = list(value) if value else []
    result.append(arg)
    return result

@register.simple_tag
def breadcrumb_item(title, url=None, active=False):
    """
    Create a breadcrumb item dictionary.
    
    Usage:
    {% breadcrumb_item "Home" "/" as home_crumb %}
    """
    return {
        'title': title,
        'url': url if url is not None else '#',
        'active': active
    }
    