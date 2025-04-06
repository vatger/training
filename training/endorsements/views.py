import os
from datetime import datetime, timedelta

from django.contrib.auth.decorators import login_required
from django.contrib.auth.models import User
from django.shortcuts import render, redirect
from django.utils import timezone
from dotenv import load_dotenv

from training.helpers import log_admin_action

from .helpers import get_tier1_endorsements
from .models import EndorsementGroup, EndorsementActivity

load_dotenv()


def valid_removal(endorsement: EndorsementActivity) -> bool:
    no_min_hours = endorsement.activity < float(os.getenv("T1_MIN_HOURS"))
    six_months_ago = timezone.now() - timedelta(days=180)
    not_recent = endorsement.created < six_months_ago
    return no_min_hours and not_recent


@login_required
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
                    "removal": (
                        (activity.removal_date - timezone.now().date()).days
                        if activity.removal_date
                        else 0
                    ),
                    "endorsement_id": activity.id,
                }
            )
        tot_res[group.name] = res
    return render(
        request,
        "endorsements/endorsements.html",
        {"endorsements": tot_res},
    )


@login_required
def remove_tier1(request, endorsement_id: int):
    try:
        endorsement = EndorsementActivity.objects.get(id=endorsement_id)
    except EndorsementActivity.DoesNotExist:
        return redirect("endorsements:overview")
    # Check whether user can mentor any of the linked courses
    courses = endorsement.group.courses.all()
    if not courses.filter(mentors=request.user).exists():
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
    # Log the removal
    log_admin_action(
        request.user,
        endorsement,
        2,
        f"Removal process for {endorsement.id} started by {request.user.username}.",
    )
    return redirect("endorsements:overview")


@login_required
def trainee_view(request):
    tier_1 = get_tier1_endorsements()
    tier_1 = [t1 for t1 in tier_1 if t1["user_cid"] == int(request.user.username)]
    res_t1 = []
    for endorsement in tier_1:
        entry = {}
        try:
            activity = EndorsementActivity.objects.get(id=endorsement["id"])
        except EndorsementActivity.DoesNotExist:
            continue
        entry["activity"] = round(activity.activity / 60, 1)
        entry["position"] = endorsement["position"]
        entry["removal_date"] = activity.removal_date
        res_t1.append(entry)
    return render(request, "endorsements/trainee.html", {"tier_1": res_t1})
