import os

from django.shortcuts import redirect
from django.contrib.auth import logout
from django.contrib.auth import login
from django.contrib.auth.models import User, Group
from django.http import HttpResponseRedirect
from .models import UserDetail
from dotenv import load_dotenv
from authlib.integrations.django_client import OAuth


load_dotenv()


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
        "scope": "name rating assignment teams",  # teams",
        "token_endpoint_auth_method": "client_secret_basic",
        "token_placement": "header",
    },
)


def login_view(request):
    if request.user.is_authenticated:
        return redirect("/waitinglists")

    vatger = oauth.create_client("vatger")
    redirect_uri = os.getenv("OAUTH_REDIRECT_URL")
    return vatger.authorize_redirect(request, redirect_uri)


def callback_view(request):
    vatger = oauth.create_client("vatger")
    token = vatger.authorize_access_token(request)
    resp = vatger.get("userinfo", token=token)
    resp.raise_for_status()
    profile = resp.json()
    print(profile)
    user, created = User.objects.get_or_create(
        username=profile["id"],
        defaults={
            "first_name": profile["firstname"],
            "last_name": profile["lastname"],
            "is_staff": "S1 Mentor" in profile["teams"]
            or "ATD Leitung" in profile["teams"],
        },
    )
    if "S1 Mentor" in profile["teams"] or "ATD Leitung" in profile["teams"]:
        mentor_group = Group.objects.get(name="Mentor")
        user.groups.add(mentor_group)

    UserDetail.objects.update_or_create(
        user=user, subdivision=profile["subdivision_code"], rating=profile["rating_atc"]
    )

    login(request, user)
    return redirect("/waitinglists")


def logout_view(request):
    logout(request)
    return HttpResponseRedirect("https://vatger.de")
