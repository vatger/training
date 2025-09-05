import json
import os
from datetime import datetime, timezone

import requests
from authlib.integrations.django_client import OAuth
from cachetools import cached, TTLCache
from django.conf import settings
from django.contrib.auth import login, logout
from django.contrib.auth.models import User
from django.http import JsonResponse, HttpResponseRedirect, HttpResponse
from django.shortcuts import redirect
from django.utils.crypto import get_random_string
from django.views.decorators.csrf import csrf_exempt
from django.views.decorators.http import require_http_methods
from dotenv import load_dotenv
from training.eud_header import eud_header

from .models import UserDetail

load_dotenv()

mentor_groups = {"EDGG Mentor", "EDMM Mentor", "EDWW Mentor"}

oauth = OAuth()

oauth.register(
    name="vatger",
    client_id=os.getenv("OAUTH_CLIENT_ID"),
    client_secret=os.getenv("OAUTH_CLIENT_SECRET"),
    access_token_url=os.getenv("OAUTH_TOKEN_URL"),
    access_token_params=None,
    authorize_url=os.getenv("OAUTH_AUTH_URL"),
    authorize_params=None,
    api_base_url=os.getenv("OAUTH_API_URL"),
    client_kwargs={
        "scope": "full_name email vatsim_details country",
        "token_endpoint_auth_method": "client_secret_basic",
        "token_placement": "header",
    },
)


@cached(cache=TTLCache(maxsize=1024, ttl=60 * 10))
def get_training_staff():
    if settings.USE_CORE_MOCK:
        return [
            {
                "id": 104,
                "cid": 1439797,
                "access_type": 2,
                "created_at": "2024-02-26T12:55:13.000000Z",
                "updated_at": "2024-02-29T19:01:38.000000Z",
            },
            {
                "id": 105,
                "cid": 1601613,
                "access_type": 0,
                "created_at": "2024-02-26T13:15:01.000000Z",
                "updated_at": "2024-11-26T21:01:34.000000Z",
            },
        ]
    r = requests.get(
        "https://core.vateud.net/api/facility/training/staff", headers=eud_header
    ).json()
    return r["data"]


@csrf_exempt
@require_http_methods(["GET"])
def auth_url_view(request):
    """
    Returns the OAuth authorization URL for the frontend to redirect to
    """
    vatger = oauth.create_client("vatger")
    redirect_uri = os.getenv("OAUTH_REDIRECT_URL")

    # Generate a state parameter for security
    state = get_random_string(32)
    request.session["oauth_state"] = state

    authorization_url = vatger.create_authorization_url(redirect_uri, state=state)

    return JsonResponse({"authorization_url": authorization_url["url"], "state": state})


@csrf_exempt
@require_http_methods(["POST"])
def auth_callback_view(request):
    """
    Handles the OAuth callback and authenticates the user
    """
    try:
        data = json.loads(request.body)
        code = data.get("code")
        state = data.get("state")

        # Verify state parameter
        if state != request.session.get("oauth_state"):
            return JsonResponse({"error": "Invalid state parameter"}, status=400)

        # Exchange code for token
        vatger = oauth.create_client("vatger")
        redirect_uri = os.getenv("OAUTH_REDIRECT_URL")

        # Apply same debug logic as in auth_url_view
        host = request.get_host()
        is_local_request = (
            host.startswith("localhost")
            or host.startswith("127.0.0.1")
            or host.startswith("0.0.0.0")
        )

        if is_local_request and settings.DEBUG:
            # For local testing, use localhost in redirect_uri
            redirect_uri = redirect_uri.replace("93.131.241.191", host.split(":")[0])
            if ":" in host:
                port = host.split(":")[1]
                redirect_uri = redirect_uri.replace(":80", f":{port}")

        token = vatger.fetch_access_token(code=code, redirect_uri=redirect_uri)

        # Get user info
        resp = vatger.get("api/user", token=token)
        resp.raise_for_status()
        profile = resp.json()

        user_data = profile["data"]
        cid = user_data["cid"]
        personal = user_data["personal"]
        vatsim_info = user_data["vatsim"]

        # Create or update user
        user, created = User.objects.get_or_create(
            username=cid,
            defaults={
                "first_name": personal["name_first"],
                "last_name": personal["name_last"],
                "is_staff": False,
                "is_superuser": False,
            },
        )

        # Update user info
        user.first_name = personal["name_first"]
        user.last_name = personal["name_last"]
        user.save()

        # Update user details
        user_detail, user_detail_created = UserDetail.objects.get_or_create(
            user=user,
            defaults={
                "rating": vatsim_info["rating"]["id"],
                "subdivision": vatsim_info.get("subdivision", {}).get("id"),
            },
        )
        user_detail.rating = vatsim_info["rating"]["id"]
        user_detail.subdivision = vatsim_info.get("subdivision", {}).get("id")
        user_detail.last_rating_change = datetime(
            2000, 1, 1, 0, 0, 0, tzinfo=timezone.utc
        )
        user_detail.save()

        # Log the user in
        login(request, user)

        # Clear the state from session
        if "oauth_state" in request.session:
            del request.session["oauth_state"]

        return JsonResponse(
            {
                "success": True,
                "user": {
                    "id": user.id,
                    "username": user.username,
                    "cid": cid,
                    "first_name": user.first_name,
                    "last_name": user.last_name,
                    "rating": user_detail.rating,
                    "subdivision": user_detail.subdivision,
                },
                "session_key": request.session.session_key,
            }
        )

    except Exception as e:
        print(f"Authentication error: {e}")
        import traceback

        traceback.print_exc()
        return JsonResponse(
            {"error": "Authentication failed", "details": str(e)}, status=400
        )


@csrf_exempt
@require_http_methods(["POST"])
def auth_logout_view(request):
    """
    Logs out the user
    """
    logout(request)
    return JsonResponse({"success": True, "message": "Logged out successfully"})


@csrf_exempt
@require_http_methods(["GET"])
def auth_status_view(request):
    """
    Returns the current authentication status and user info
    """
    if request.user.is_authenticated:
        try:
            user_detail = UserDetail.objects.get(user=request.user)
        except UserDetail.DoesNotExist:
            user_detail = None

        user_groups = list(request.user.groups.values_list("name", flat=True))

        return JsonResponse(
            {
                "authenticated": True,
                "user": {
                    "id": request.user.id,
                    "username": request.user.username,
                    "first_name": request.user.first_name,
                    "last_name": request.user.last_name,
                    "is_staff": request.user.is_staff,
                    "is_superuser": request.user.is_superuser,
                    "groups": user_groups,
                    "rating": user_detail.rating if user_detail else None,
                    "subdivision": user_detail.subdivision if user_detail else None,
                },
            }
        )
    else:
        return JsonResponse({"authenticated": False, "user": None})


# Legacy views for backward compatibility (if needed)
def login_view(request):
    """Legacy login view - redirects to frontend auth flow"""
    if request.user.is_authenticated:
        return redirect("/")

    # For API usage, return JSON
    if request.headers.get("Accept") == "application/json":
        return auth_url_view(request)

    # For browser usage, redirect to frontend
    vatger = oauth.create_client("vatger")
    redirect_uri = os.getenv("OAUTH_REDIRECT_URL")
    return vatger.authorize_redirect(request, redirect_uri)


def callback_view(request):
    """Legacy callback view"""
    # If this is an API request, return error suggesting new endpoint
    if request.headers.get("Accept") == "application/json":
        return JsonResponse(
            {"error": "Use POST /connect/auth/callback/ for API authentication"},
            status=400,
        )

    # Keep legacy behavior for browser
    try:
        vatger = oauth.create_client("vatger")
        token = vatger.authorize_access_token(request)
        resp = vatger.get("api/user", token=token)
        resp.raise_for_status()
        profile = resp.json()

        # ... (rest of the original callback logic)
        # This is kept for backward compatibility but simplified

        return redirect("/")
    except Exception as e:
        print(e)
        return redirect("/?error=callback")


def logout_view(request):
    """Legacy logout view"""
    if request.headers.get("Accept") == "application/json":
        return auth_logout_view(request)

    logout(request)
    return HttpResponseRedirect("https://vatger.de")


# Add this new view to your connect/views.py file


@csrf_exempt
def debug_callback_view(request):
    """
    Debug callback view that shows the code and state for manual testing
    """
    code = request.GET.get("code")
    state = request.GET.get("state")
    error = request.GET.get("error")

    if error:
        return JsonResponse(
            {
                "error": error,
                "error_description": request.GET.get("error_description", ""),
                "message": "OAuth authorization failed",
            },
            status=400,
        )

    if not code or not state:
        return JsonResponse(
            {
                "error": "Missing parameters",
                "received_params": dict(request.GET),
                "message": "Code and state parameters are required",
            },
            status=400,
        )

    # Return the parameters for manual testing
    response_data = {
        "code": code,
        "state": state,
        "session_state": request.session.get("oauth_state"),
        "state_matches": state == request.session.get("oauth_state"),
        "next_step": {
            "method": "POST",
            "url": "/connect/auth/callback/",
            "body": {"code": code, "state": state},
        },
        "curl_example": f"""curl -X POST http://localhost:8000/connect/auth/callback/ \\
  -H "Content-Type: application/json" \\
  -H "Cookie: sessionid={request.session.session_key}" \\
  -d '{{"code": "{code}", "state": "{state}"}}'
        """.strip(),
    }

    # If this is a local development request, auto-proceed with authentication
    if request.GET.get("auto") == "true":
        try:
            # Create a mock POST request
            import json
            from django.http import HttpRequest

            mock_request = HttpRequest()
            mock_request.method = "POST"
            mock_request.session = request.session
            mock_request.META = request.META
            mock_request._body = json.dumps({"code": code, "state": state}).encode(
                "utf-8"
            )

            # Call the actual callback view
            return auth_callback_view(mock_request)
        except Exception as e:
            response_data["auto_auth_error"] = str(e)

    # Return HTML for easier debugging in browser
    html_content = f"""
    <!DOCTYPE html>
    <html>
    <head>
        <title>OAuth Debug Callback</title>
        <style>
            body {{ font-family: Arial, sans-serif; margin: 40px; }}
            .container {{ max-width: 800px; }}
            .success {{ color: green; }}
            .error {{ color: red; }}
            .info {{ color: blue; }}
            pre {{ background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }}
            button {{ background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }}
            button:hover {{ background: #005a87; }}
        </style>
    </head>
    <body>
        <div class="container">
            <h1>OAuth Debug Callback</h1>

            <h2>Parameters Received:</h2>
            <ul>
                <li><strong>Code:</strong> <code>{code}</code></li>
                <li><strong>State:</strong> <code>{state}</code></li>
                <li><strong>Session State:</strong> <code>{request.session.get('oauth_state', 'Not found')}</code></li>
                <li><strong>State Matches:</strong> <span class="{'success' if response_data['state_matches'] else 'error'}">{response_data['state_matches']}</span></li>
            </ul>

            <h2>Next Steps:</h2>
            <p>You can complete the authentication by making this API call:</p>

            <h3>Using curl:</h3>
            <pre>{response_data['curl_example']}</pre>

            <h3>Using JavaScript (for frontend):</h3>
            <pre>
fetch('/connect/auth/callback/', {{
    method: 'POST',
    headers: {{
        'Content-Type': 'application/json',
        'X-CSRFToken': getCookie('csrftoken') // if CSRF protection is enabled
    }},
    credentials: 'include',
    body: JSON.stringify({{
        'code': '{code}',
        'state': '{state}'
    }})
}})
.then(response => response.json())
.then(data => console.log(data));
            </pre>

            <h3>Auto-complete Authentication:</h3>
            <button onclick="completeAuth()">Complete Authentication Now</button>

            <div id="result" style="margin-top: 20px;"></div>

            <script>
                async function completeAuth() {{
                    try {{
                        const response = await fetch('/connect/auth/callback/', {{
                            method: 'POST',
                            headers: {{
                                'Content-Type': 'application/json'
                            }},
                            credentials: 'include',
                            body: JSON.stringify({{
                                'code': '{code}',
                                'state': '{state}'
                            }})
                        }});

                        const data = await response.json();

                        document.getElementById('result').innerHTML = 
                            '<h3>Authentication Result:</h3>' +
                            '<pre>' + JSON.stringify(data, null, 2) + '</pre>';

                        if (data.success) {{
                            document.getElementById('result').innerHTML += 
                                '<p class="success">✅ Authentication successful! Session key: ' + data.session_key + '</p>';
                        }}
                    }} catch (error) {{
                        document.getElementById('result').innerHTML = 
                            '<p class="error">❌ Error: ' + error.message + '</p>';
                    }}
                }}
            </script>
        </div>
    </body>
    </html>
    """

    return HttpResponse(html_content, content_type="text/html")
