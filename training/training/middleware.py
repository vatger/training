from django.contrib.auth import get_user_model
from django.contrib.auth.models import AnonymousUser
from django.contrib.sessions.models import Session
from django.utils.deprecation import MiddlewareMixin

User = get_user_model()


class SessionTokenAuthenticationMiddleware(MiddlewareMixin):
    """
    Middleware to authenticate users via session key in Authorization header
    for API requests. Falls back to standard session authentication.
    """

    def process_request(self, request):
        # Skip if user is already authenticated via standard session
        if hasattr(request, "user") and request.user.is_authenticated:
            return None

        # Check for Authorization header with session key
        auth_header = request.META.get("HTTP_AUTHORIZATION")
        if auth_header and auth_header.startswith("Session "):
            session_key = auth_header.split(" ", 1)[1]

            try:
                session = Session.objects.get(session_key=session_key)
                session_data = session.get_decoded()
                user_id = session_data.get("_auth_user_id")

                if user_id:
                    user = User.objects.get(pk=user_id)
                    request.user = user
                    # Set the session for this request
                    request.session = session_data
                else:
                    request.user = AnonymousUser()
            except (Session.DoesNotExist, User.DoesNotExist):
                request.user = AnonymousUser()

        return None
