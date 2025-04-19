import os

from authlib.integrations.django_client import OAuth
from cachetools import cached, TTLCache
from django.conf import settings
from django.contrib.auth import login
from django.contrib.auth import logout
from django.contrib.auth.models import User, Group
from django.http import HttpResponseRedirect
from django.shortcuts import redirect
from dotenv import load_dotenv
import requests

from .models import UserDetail
from training.eud_header import eud_header

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
        "scope": "name rating assignment teams",  # teams",
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
                "cid": 1000000,
                "access_type": 2,
                "created_at": "2024-02-26T13:15:01.000000Z",
                "updated_at": "2024-11-26T21:01:34.000000Z",
            },
        ]
    r = requests.get(
        "https://core.vateud.net/api/facility/training/staff", headers=eud_header
    ).json()
    return r["data"]


def login_view(request):
    if request.user.is_authenticated:
        return redirect("/")

    vatger = oauth.create_client("vatger")
    redirect_uri = os.getenv("OAUTH_REDIRECT_URL")
    return vatger.authorize_redirect(request, redirect_uri)


def callback_view(request):
    try:
        vatger = oauth.create_client("vatger")
        token = vatger.authorize_access_token(request)
        resp = vatger.get("userinfo", token=token)
        resp.raise_for_status()
        profile = resp.json()
        user, created = User.objects.get_or_create(
            username=profile["id"],
            defaults={
                "first_name": profile["firstname"],
                "last_name": profile["lastname"],
                "is_staff": len(mentor_groups & set(profile["teams"])) > 0
                or "ATD Leitung" in profile["teams"]
                or "VATGER Leitung" in profile["teams"],
                "is_superuser": "ATD Leitung" in profile["teams"]
                or "VATGER Leitung" in profile["teams"],
            },
        )
        user.first_name = profile["firstname"]
        user.last_name = profile["lastname"]
        user.is_staff = (
            len(mentor_groups & set(profile["teams"])) > 0
            or "ATD Leitung" in profile["teams"]
            or "VATGER Leitung" in profile["teams"]
        )
        user.is_superuser = (
            "ATD Leitung" in profile["teams"] or "VATGER Leitung" in profile["teams"]
        )
        user.save()
        is_mentor = False
        for group in mentor_groups:
            if group in profile["teams"]:
                if not is_mentor:
                    # Automatically assign core mentor roles if not exist already
                    is_mentor = True
                    staff = get_training_staff()
                    selected = [d for d in staff if d["cid"] == int(profile["id"])]
                    if not selected:
                        if settings.USE_CORE_MOCK:
                            continue
                        requests.post(
                            f"https://core.vateud.net/api/facility/training/assign/{int(profile["id"])}/mentor",
                            headers=eud_header,
                            data={"user_cid": profile["id"]},
                        )
                mentor_group = Group.objects.get(name=group)
                user.groups.add(mentor_group)

        user_detail, user_detail_created = UserDetail.objects.get_or_create(
            user=user,
            defaults={
                "rating": profile["rating_atc"],
                "subdivision": profile["subdivision_code"],
            },
        )
        user_detail.rating = profile["rating_atc"]
        user_detail.subdivision = profile["subdivision_code"]
        user_detail.save()

        login(request, user)
        return redirect("/")
    except Exception as e:
        print(e)
        return redirect("/?error=callback")


def logout_view(request):
    logout(request)
    return HttpResponseRedirect("https://vatger.de")
