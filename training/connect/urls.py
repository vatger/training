from django.urls import path

from . import views

urlpatterns = [
    # REST API endpoints
    path("auth/url/", views.auth_url_view, name="auth-url"),
    path("auth/callback/", views.auth_callback_view, name="auth-callback"),
    path("auth/logout/", views.auth_logout_view, name="auth-logout"),
    path("auth/status/", views.auth_status_view, name="auth-status"),
    # Debug endpoint for local development
    path("debug-callback/", views.debug_callback_view, name="debug-callback"),
    # Legacy endpoints (for backward compatibility)
    path("login/", views.login_view, name="login"),
    path("callback/", views.callback_view, name="callback"),
    path("logout/", views.logout_view, name="logout"),
]
