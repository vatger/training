from django.shortcuts import render, redirect
from .models import EndorsementGroup, EndorsementActivity
from .helpers import get_tier1_endorsements
from django.contrib.auth.models import User
from datetime import datetime, timedelta
import os
from dotenv import load_dotenv
from django.utils import timezone

load_dotenv()


def valid_removal(endorsement: EndorsementActivity) -> bool:
    no_min_hours = endorsement.activity < float(os.getenv("T1_MIN_HOURS"))
    six_months_ago = timezone.now() - timedelta(days=180)
    not_recent = endorsement.created < six_months_ago
    return no_min_hours and not_recent


# TODO: mentor only
def overview(request):
    groups = EndorsementGroup.objects.filter(
        courses__in=request.user.mentored_courses.all()
    ).distinct()
    endorsements = get_tier1_endorsements()
    tot_res = {}
    for group in groups:
        res = []
        for endorsement in endorsements:
            if group.name != endorsement["position"]:
                continue
            try:
                activity = EndorsementActivity.objects.get(id=endorsement["id"])
            except EndorsementActivity.DoesNotExist:
                continue
            if not valid_removal(activity):
                continue
            try:
                user = User.objects.get(username=endorsement["user_cid"])
                name = user.get_full_name()
            except User.DoesNotExist:
                name = "Unknown"
            res.append(
                {
                    "id": endorsement["user_cid"],
                    "activity": round(activity.activity / 60, 2),
                    "name": name,
                    "removal": True if activity.removal_date else False,
                    "endorsement_id": activity.id,
                }
            )
            # res[endorsement["user_cid"]] = activity.activity
        tot_res[group.name] = res
    return render(
        request,
        "endorsements/endorsements.html",
        {"endorsements": tot_res},
    )


# TODO: mentor of correct course only
def remove_tier1(request, endorsement_id: int):
    try:
        endorsement = EndorsementActivity.objects.get(id=endorsement_id)
    except EndorsementActivity.DoesNotExist:
        return redirect("endorsements:overview")
    # Check if removal date is already set
    if endorsement.removal_date:
        return redirect("endorsements:overview")
    # Check valid removal
    if not valid_removal(endorsement):
        endorsement.removal_date = None
        endorsement.save()
        return redirect("endorsements:overview")
    # Set removal date to 31 days from now
    endorsement.removal_date = datetime.now() + timedelta(days=31)
    # Set updated to zero unix time to trigger update
    endorsement.updated = datetime(1975, 1, 1, 0, 0, 0)
    endorsement.save()
    return redirect("endorsements:overview")
