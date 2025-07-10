import os
from datetime import datetime, timedelta

import requests
from django.contrib.auth.decorators import login_required
from django.contrib.auth.models import User
from django.http import JsonResponse
from django.shortcuts import render, redirect, get_object_or_404
from django.urls import reverse
from django.utils import timezone
from django.views.decorators.http import require_http_methods
from dotenv import load_dotenv
from overview.helpers.trainee import get_course_completion
from training.eud_header import eud_header
from training.helpers import log_admin_action
from training.permissions import mentor_required

from .helpers import get_tier1_endorsements, get_tier2_endorsements
from .models import EndorsementGroup, EndorsementActivity, Tier2Endorsement

load_dotenv()

min_hours_required = float(os.getenv("T1_MIN_MINUTES")) / 60


def valid_removal(endorsement: EndorsementActivity, day_delta: int = 180) -> bool:
    no_min_hours = endorsement.activity < float(os.getenv("T1_MIN_MINUTES"))
    six_months_ago = timezone.now() - timedelta(days=day_delta)
    not_recent = endorsement.created < six_months_ago
    return no_min_hours and not_recent


@mentor_required
def overview(request):
    groups = (
        EndorsementGroup.objects.filter(courses__in=request.user.mentored_courses.all())
        .distinct()
        .order_by("name")
    )

    endorsements = get_tier1_endorsements()

    total_endorsements = 0
    inactive_count = 0
    removal_count = 0

    endorsements_by_group = {}

    for group in groups:
        group_endorsements = []

        for endorsement in endorsements:
            if group.name != endorsement["position"]:
                continue

            try:
                activity = EndorsementActivity.objects.get(id=endorsement["id"])
            except EndorsementActivity.DoesNotExist:
                continue

            if not valid_removal(
                activity, day_delta=5 * 30 + 5
            ):  # 5 days extra to prevent weird edge cases
                continue

            try:
                user = User.objects.get(username=endorsement["user_cid"])
                name = user.get_full_name()
            except User.DoesNotExist:
                name = "Unknown"

            activity_hours = round(activity.activity / 60, 2)

            removal_days = -1
            if activity.removal_date:
                removal_days = (activity.removal_date - timezone.now().date()).days
                if removal_days >= 0:
                    removal_count += 1

            if activity_hours < min_hours_required:
                inactive_count += 1

            bar_width = (
                100
                if activity_hours >= min_hours_required
                else min(100, (activity_hours / min_hours_required) * 100)
            )

            group_endorsements.append(
                {
                    "id": endorsement["user_cid"],
                    "activity": activity_hours,
                    "name": name,
                    "removal": removal_days,
                    "endorsement_id": activity.id,
                    "bar_width": round(bar_width, 0),
                }
            )

            total_endorsements += 1

        endorsements_by_group[group.name] = group_endorsements

    return render(
        request,
        "endorsements/mentor.html",
        {
            "endorsement_groups": groups,
            "endorsements": endorsements_by_group,
            "min_hours_required": min_hours_required,
            "low_activity_description": f"Below {min_hours_required} hours of activity.",
            "total_endorsements": total_endorsements,
            "inactive_count": inactive_count,
            "removal_count": removal_count,
            "min_hours": min_hours_required,
            "half_min_hours": min_hours_required / 2,
        },
    )


@mentor_required
@require_http_methods(["POST"])
def remove_tier1(request, endorsement_id: int):
    try:
        endorsement = EndorsementActivity.objects.get(id=endorsement_id)
    except EndorsementActivity.DoesNotExist:
        return JsonResponse(
            {
                "success": False,
                "error": "Endorsement not found",
                "redirect": True,
                "redirect_url": reverse("endorsements:overview"),
            },
            status=404,
        )

    # Check whether user can mentor any of the linked courses
    courses = endorsement.group.courses.all()
    if not courses.filter(mentors=request.user).exists():
        return JsonResponse(
            {
                "success": False,
                "error": "Permission denied",
                "redirect": True,
                "redirect_url": reverse("endorsements:overview"),
            },
            status=403,
        )

    # Check if removal date is already set
    if endorsement.removal_date:
        return JsonResponse(
            {
                "success": False,
                "error": "Removal date already set",
                "redirect": True,
                "redirect_url": reverse("endorsements:overview"),
            },
            status=400,
        )

    # Check valid removal
    if not valid_removal(endorsement):
        endorsement.removal_date = None
        endorsement.save()
        return JsonResponse(
            {
                "success": False,
                "error": "Invalid removal",
                "redirect": True,
                "redirect_url": reverse("endorsements:overview"),
            },
            status=400,
        )

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

    return JsonResponse(
        {
            "success": True,
            "message": f"Removal process started for {endorsement.id}, {endorsement.group.name}",
            "removal_date": endorsement.removal_date.strftime("%Y-%m-%d %H:%M:%S"),
        }
    )


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

        activity_hours = round(activity.activity / 60, 1)
        entry["activity"] = round(activity.activity / 60, 1)
        entry["position"] = endorsement["position"]
        entry["removal_date"] = activity.removal_date
        entry["updated"] = activity.updated.strftime("%d.%m.%Y")

        if activity_hours >= min_hours_required:
            entry["bar_width"] = 100
        else:
            entry["bar_width"] = int((activity_hours / min_hours_required) * 100)

        res_t1.append(entry)

    res_t1 = sorted(res_t1, key=lambda x: x["position"])

    tier_2 = get_tier2_endorsements()
    tier_2 = [
        t2["position"] for t2 in tier_2 if t2["user_cid"] == int(request.user.username)
    ]

    available_t2 = Tier2Endorsement.objects.all()
    available_t2 = sorted(available_t2, key=lambda x: x.name)
    res_t2 = []

    for endorsement in available_t2:
        entry = {
            "position": endorsement.position,
            "name": endorsement.name,
            "moodle_id": endorsement.moodle_course_id,
            "has_endorsement": endorsement.position in tier_2,
            "moodle_completed": (
                True
                if endorsement.position in tier_2
                else get_course_completion(
                    int(request.user.username), endorsement.moodle_course_id
                )
            ),
            "id": endorsement.id,
        }
        res_t2.append(entry)

    return render(
        request,
        "endorsements/trainee.html",
        {
            "tier_1": res_t1,
            "tier_2": res_t2,
            "min_hours": min_hours_required,
            "half_min_hours": min_hours_required / 2,
        },
    )


@login_required
def request_tier_2(request, endorsement_id: int):
    endorsement = get_object_or_404(Tier2Endorsement, id=endorsement_id)

    if not get_course_completion(
        int(request.user.username), endorsement.moodle_course_id
    ):
        return redirect("endorsements:trainee_view")

    t2 = get_tier2_endorsements()
    t2_user = [
        t2
        for t2 in t2
        if t2["user_cid"] == int(request.user.username)
        and t2["position"] == endorsement.position
    ]

    if t2_user:
        return redirect("endorsements:trainee_view")

    requests.post(
        "https://core.vateud.net/api/facility/endorsements/tier-2",
        headers=eud_header,
        data={
            "user_cid": int(request.user.username),
            "position": endorsement.position,
            "instructor_cid": os.getenv("ATD_LEAD_CID"),
        },
    )

    return redirect("endorsements:trainee_view")
