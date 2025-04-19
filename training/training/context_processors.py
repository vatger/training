def breadcrumbs(request):
    """
    Context processor that provides breadcrumb data for templates.
    
    This automatically generates basic breadcrumbs based on the current URL.
    Can be overridden in views by explicitly setting 'breadcrumbs' in the context.
    """
    path = request.path.strip('/')
    parts = path.split('/')
    
    # Default breadcrumbs map - maps URL prefixes to (title, url) tuples
    url_map = {
        'trainee': ('Trainee Dashboard', '/trainee/'),
        'logs': ('Training Logs', '#'),
        'endorsements': ('Endorsements', '/endorsements/trainee/'),
        'lists': ('Available Courses', '/lists/'),
        'overview': ('Trainee Overview', '/overview/'),
    }
    
    # Always include home
    breadcrumbs = [
        {'title': 'Dashboard', 'url': '/'}
    ]
    
    # Add breadcrumbs based on URL path
    if parts and parts[0]:
        section = parts[0]
        if section in url_map:
            title, url = url_map[section]
            breadcrumbs.append({'title': title, 'url': url})
    
    # The context processor just provides default breadcrumbs
    # Views can override this by explicitly setting their own breadcrumbs in context
    return {'default_breadcrumbs': breadcrumbs}