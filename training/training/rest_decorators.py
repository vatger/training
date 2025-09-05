from functools import wraps

from django.contrib.auth.models import AnonymousUser
from django.http import JsonResponse


def api_login_required(view_func):
    """
    Decorator for API views that require authentication.
    Returns JSON response instead of redirecting to login page.
    """

    @wraps(view_func)
    def _wrapped_view(request, *args, **kwargs):
        if isinstance(request.user, AnonymousUser) or not request.user.is_authenticated:
            return JsonResponse(
                {
                    "error": "Authentication required",
                    "message": "You must be logged in to access this resource",
                },
                status=401,
            )
        return view_func(request, *args, **kwargs)

    return _wrapped_view


def api_mentor_required(view_func):
    """
    Decorator for API views that require mentor or admin privileges.
    Returns JSON response instead of redirecting.
    """
    mentor_groups = ["EDGG Mentor", "EDMM Mentor", "EDWW Mentor"]

    @wraps(view_func)
    def _wrapped_view(request, *args, **kwargs):
        if isinstance(request.user, AnonymousUser) or not request.user.is_authenticated:
            return JsonResponse(
                {
                    "error": "Authentication required",
                    "message": "You must be logged in to access this resource",
                },
                status=401,
            )

        is_mentor = request.user.groups.filter(name__in=mentor_groups).exists()
        if not (is_mentor or request.user.is_superuser):
            return JsonResponse(
                {
                    "error": "Insufficient permissions",
                    "message": "You must be a mentor or admin to access this resource",
                },
                status=403,
            )

        return view_func(request, *args, **kwargs)

    return _wrapped_view


def api_permission_required(permission):
    """
    Decorator for API views that require specific permissions.
    Returns JSON response instead of redirecting.
    """

    def decorator(view_func):
        @wraps(view_func)
        def _wrapped_view(request, *args, **kwargs):
            if (
                isinstance(request.user, AnonymousUser)
                or not request.user.is_authenticated
            ):
                return JsonResponse(
                    {
                        "error": "Authentication required",
                        "message": "You must be logged in to access this resource",
                    },
                    status=401,
                )

            if not request.user.has_perm(permission):
                return JsonResponse(
                    {
                        "error": "Insufficient permissions",
                        "message": f"You need {permission} permission to access this resource",
                    },
                    status=403,
                )

            return view_func(request, *args, **kwargs)

        return _wrapped_view

    return decorator
