import json
import os

import requests
from cachetools import TTLCache
from django.contrib.admin.models import CHANGE
from django.contrib.auth.decorators import login_required
from django.core.checks import messages
from django.http import JsonResponse
from django.shortcuts import (
    render,
    get_object_or_404,
    HttpResponseRedirect,
    reverse,
)
from django.utils.safestring import mark_safe
from dotenv import load_dotenv
from training.helpers import log_admin_action
from training.permissions import mentor_required

from familiarisations.models import Familiarisation
from lists.helpers import course_valid_for_user, get_user_endorsements, get_roster
from overview.helpers import inform_user_course_start
from .models import Course, WaitingListEntry

load_dotenv()

# Minimum required activity hours
ACTIVITY_MIN = 10
DISPLAY_ACTIVITY = 8  # Display activity, 20% leniency
MIN_HOURS = 25


def enrol_into_required_moodles(user_id, course_ids: list):
    header = {"Authorization": f"Token {os.getenv('VATGER_API_KEY')}"}
    for course_id in course_ids:
        requests.get(
            f"http://vatsim-germany.org/api/moodle/course/{course_id}/user/{user_id}/enrol",
            headers=header,
        )


connections_cache = TTLCache(maxsize=float("inf"), ttl=10 * 60 * 60)


def get_connections(user):
    api_url = f"https://api.vatsim.net/api/ratings/{user.username}/atcsessions"
    try:
        res = requests.get(api_url).json()
        response = res["results"]
    except:
        print("Error fetching data from VATSIM API")
        return -1

    twr_s1 = sum(
        float(session["minutes_on_callsign"])
        for session in response
        if session["callsign"].split("_")[-1] == "TWR"
        and session["rating"] == 2
        and session["callsign"].split("_")[1] != "I"
    )
    twr_s2 = sum(
        float(session["minutes_on_callsign"])
        for session in response
        if session["callsign"].split("_")[-1] == "TWR"
        and session["rating"] == 3
        and session["callsign"].split("_")[1] != "I"
    )
    app_s3 = sum(
        float(session["minutes_on_callsign"])
        for session in response
        if session["callsign"].split("_")[-1] == "APP"
    )

    result = (twr_s1 / 60, twr_s2 / 60, app_s3 / 60)

    # Store in cache only if the request was successful
    connections_cache[user.username] = result
    return result


# Wrapper function to check cache first
def get_cached_connections(user):
    if user.username in connections_cache:
        return connections_cache[user.username]
    return get_connections(user)


@login_required
def view_lists(request):
    # make sure user is not currently active_trainee
    courses = Course.objects.filter(
        min_rating__lte=request.user.userdetail.rating,
        max_rating__gte=request.user.userdetail.rating,
    ).exclude(active_trainees=request.user)

    if (
        request.user.userdetail.subdivision == "GER"
        and int(request.user.username) not in get_roster()
    ):
        courses = courses.filter(type="RST")
    else:
        courses = courses.exclude(type="RST")

    # Check whether user is already in a RTG course
    if request.user.active_courses.all().filter(type="RTG").exists():
        courses = courses.exclude(type="RTG")

    # If a user is not assigned to VATSIM Germany, they cannot see RTG courses
    if request.user.userdetail.subdivision != "GER":
        courses = courses.exclude(type="RTG")
    if request.user.userdetail.subdivision == "GER":
        courses = courses.exclude(type="GST")

    try:
        twr_s1, twr_s2, app_s3 = get_cached_connections(request.user)
        error = False
    except:
        twr_s1, twr_s2, app_s3 = 0, 0, 0
        error = True

    hours_dict = {
        # temporary change to disable check for GND/TWR check
        "GND": MIN_HOURS + 1,  # twr_s1,
        "TWR": MIN_HOURS * 1,  # twr_s1,
        "APP": twr_s2,
        "CTR": app_s3,
    }

    courses_dict = {}
    n_rtg = 0

    # Get Tier 1 Endorsement, do not show course if user already has it
    user_endorsements = get_user_endorsements(int(request.user.username))

    # Do not show familarisation courses if user already has the familiarisation
    familiarisations = list(
        Familiarisation.objects.filter(user=request.user).values_list(
            "sector", flat=True
        )
    )

    for course in courses:
        endorsement_groups = set(
            course.endorsement_groups.all().values_list("name", flat=True)
        )
        if (
            len(endorsement_groups & user_endorsements) == len(endorsement_groups)
            and len(endorsement_groups) > 0
        ):
            continue

        if course.type == "FAM" and course.familiarisation_sector is not None:
            if course.familiarisation_sector.id in familiarisations:
                continue

        res = {
            "current_hours": hours_dict.get(course.position, 0),
            "hours_reached": True,
        }

        if course.type == "RTG":
            if hours_dict.get(course.position, 0) < MIN_HOURS:
                res["hours_reached"] = False
            else:
                res["hours_reached"] = True

        try:
            entry = WaitingListEntry.objects.get(user=request.user, course=course)
            list_spot = (
                WaitingListEntry.objects.filter(
                    course=course, date_added__lt=entry.date_added
                ).count()
                + 1
            )
            res["entered"] = True
            res["list_spot"] = list_spot
            res["activity"] = round(entry.activity, 1)
            if course.type == "RTG":
                n_rtg += 1
        except WaitingListEntry.DoesNotExist:
            res["entered"] = False
            res["list_spot"] = 0
            res["activity"] = 0

        courses_dict[course] = res

    return render(
        request,
        "lists/trainee.html",
        {
            "courses": courses_dict,
            "error": error,
            "rating_reached": n_rtg >= 1,
            "min_hours": MIN_HOURS,
            "min_activity": ACTIVITY_MIN,
        },
    )


@login_required
def join_leave_list(request, course_id):
    course = get_object_or_404(Course, pk=course_id)
    try:
        entry = WaitingListEntry.objects.get(user=request.user, course=course)
        entry.delete()
    except WaitingListEntry.DoesNotExist:
        valid, reason = course_valid_for_user(course, request.user)
        if not valid:
            messages.error(request, reason)
            return HttpResponseRedirect(reverse("lists:view_lists"))

        # Check if user is already in another RTG course waiting list
        if (
            course.type == "RTG"
            and WaitingListEntry.objects.filter(
                user=request.user, course__type="RTG"
            ).exists()
        ):
            # User is already in a rating course waiting list, redirect with error message
            messages.error(
                request,
                "You are already on the waiting list for a rating course. You can only join one rating course at a time.",
            )
            return HttpResponseRedirect(reverse("lists:view_lists"))

        if course.min_rating <= request.user.userdetail.rating <= course.max_rating:
            if course.type == "RTG" and course.position not in ["GND", "TWR"]:
                try:
                    twr_s1, twr_s2, app_s3 = get_connections(request.user)
                    match course.position:
                        case "TWR":
                            if twr_s1 >= MIN_HOURS:
                                WaitingListEntry.objects.create(
                                    user=request.user, course=course
                                )
                        case "APP":
                            if twr_s2 >= MIN_HOURS:
                                WaitingListEntry.objects.create(
                                    user=request.user, course=course
                                )
                        case "CTR":
                            if app_s3 >= MIN_HOURS:
                                WaitingListEntry.objects.create(
                                    user=request.user, course=course
                                )
                except:
                    pass
            else:
                WaitingListEntry.objects.create(user=request.user, course=course)
    return HttpResponseRedirect(reverse("lists:view_lists"))


@mentor_required
def update_remarks(request):
    if request.method != "POST":
        return JsonResponse(
            {"success": False, "error": "Invalid request method"}, status=405
        )

    trainee_id = request.POST.get("trainee_id")
    remarks = request.POST.get("remarks", "")

    if not trainee_id:
        return JsonResponse(
            {"success": False, "error": "Trainee ID is required"}, status=400
        )

    try:
        entry = get_object_or_404(WaitingListEntry, pk=trainee_id)

        if (
            not request.user.is_superuser
            and entry.course not in request.user.mentored_courses.all()
        ):
            return JsonResponse(
                {
                    "success": False,
                    "error": "You don't have permission to modify this entry",
                },
                status=403,
            )

        entry.remarks = remarks
        entry.save()

        log_admin_action(
            request.user,
            entry,
            CHANGE,
            f"Updated remarks for {entry.user} ({entry.user.username}) in course {entry.course}",
        )

        return JsonResponse({"success": True})

    except Exception as e:
        return JsonResponse({"success": False, "error": str(e)}, status=500)


@mentor_required
def mentor_view(request):
    if request.user.is_superuser:
        courses = Course.objects.all()
    else:
        courses = request.user.mentored_courses.all()

    total_waiting = 0
    rtg_waiting = 0
    edmt_waiting = 0
    fam_waiting = 0
    gst_waiting = 0

    course_list = []

    for course in courses:
        if course.type == "RTG":
            waiting_entries = WaitingListEntry.objects.filter(
                course=course,  # activity__gte=DISPLAY_ACTIVITY
            ).order_by(
                "date_added"
            )  # Sort by join date, oldest first
            rtg_waiting += waiting_entries.count()
        else:
            waiting_entries = WaitingListEntry.objects.filter(course=course).order_by(
                "date_added"
            )
            if course.type == "EDMT":
                edmt_waiting += waiting_entries.count()
            elif course.type == "FAM":
                fam_waiting += waiting_entries.count()
            elif course.type == "GST":
                gst_waiting += waiting_entries.count()

        total_waiting += waiting_entries.count()

        waiting_list = []
        for entry in waiting_entries:
            first_initial = entry.user.first_name[0] if entry.user.first_name else ""
            last_initial = entry.user.last_name[0] if entry.user.last_name else ""

            waiting_list.append(
                {
                    "id": entry.id,
                    "name": f"{entry.user.first_name} {entry.user.last_name}",
                    "initials": f"{first_initial}{last_initial}",
                    "vatsim_id": entry.user.username,
                    "activity": round(entry.activity, 1),
                    "remarks": entry.remarks,
                }
            )

        course_list.append(
            {
                "id": course.id,
                "name": course.name,
                "position": course.position,
                "position_display": course.get_position_display(),
                "type": course.type,
                "type_display": course.get_type_display(),
                "waiting_count": len(waiting_list),
                "waiting_list": waiting_list,
            }
        )

    course_list.sort(key=lambda x: (x["type"], x["position"]))

    course_list_json = mark_safe(json.dumps(course_list))

    context = {
        "course_list_json": course_list_json,
        "total_waiting": total_waiting,
        "rtg_waiting": rtg_waiting,
        "edmt_waiting": edmt_waiting,
        "fam_waiting": fam_waiting,
        "activity_min": ACTIVITY_MIN,
        "activity_display": DISPLAY_ACTIVITY,
    }

    return render(request, "lists/mentor.html", context)


@mentor_required
def start_training(request, waitlist_entry_id):
    entry = get_object_or_404(WaitingListEntry, pk=waitlist_entry_id)
    if request.user not in entry.course.mentors.all() and not request.user.is_superuser:
        return HttpResponseRedirect(reverse("lists:mentor_view"))

    if entry.activity < DISPLAY_ACTIVITY and entry.course.type == "RTG":
        return HttpResponseRedirect(reverse("lists:mentor_view"))

    entry.course.active_trainees.add(entry.user)

    # Log the action
    log_admin_action(
        request.user,
        entry.course,
        CHANGE,
        f"Added trainee {entry.user} ({entry.user.username}) to course {entry.course}",
    )

    entry.delete()

    enrol_into_required_moodles(entry.user.username, entry.course.moodle_course_ids)

    inform_user_course_start(int(entry.user.username), entry.course.name)

    return HttpResponseRedirect(reverse("lists:mentor_view"))
