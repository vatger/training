from django.contrib.auth.decorators import login_required, user_passes_test
from django.shortcuts import redirect

# Define the mentor groups
mentor_groups = ["EDGG Mentor", "EDMM Mentor", "EDWW Mentor"]

def is_mentor_or_admin(user):
    return user.groups.filter(name__in=mentor_groups).exists() or user.is_superuser

def mentor_required(view_func):
    """
    Decorator for views that checks that the user is logged in and is a mentor or admin,
    redirecting to the dashboard if necessary.
    """
    decorated_view = login_required(
        user_passes_test(
            is_mentor_or_admin,
            login_url='trainee:home'
        )(view_func)
    )
    return decorated_view