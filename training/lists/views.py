import json
import os

from django.core.checks import messages
from django.shortcuts import render, redirect, get_object_or_404, HttpResponseRedirect, reverse
from django.contrib.auth.decorators import login_required
from django.utils.html import escape
from django.utils.safestring import mark_safe
import requests
from cachetools import TTLCache, cached
from django.contrib.auth.models import User

from training.permissions import mentor_required
from django.contrib.admin.models import CHANGE
from training.helpers import log_admin_action

from familiarisations.models import Familiarisation
from .models import Course, WaitingListEntry

# Minimum required activity hours
ACTIVITY_MIN = 8
MIN_HOURS = 25


# Fix for trainee signup issue
@login_required
def view_lists(request):
    # make sure user is not currently active_trainee
    courses = Course.objects.filter(
        min_rating__lte=request.user.userdetail.rating,
        max_rating__gte=request.user.userdetail.rating,
    ).exclude(active_trainees=request.user)

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
        "GND": twr_s1,
        "TWR": twr_s1,
        "APP": twr_s2,
        "CTR": app_s3,
    }

    courses_dict = {}
    n_rtg = 0

    # Get Tier 1 Endorsement, do not show course if user already has it
    user_endorsements = get_user_endorsements(int(request.user.username))

    # Get user's familiarizations
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
            "hours_reached": True
        }

        if course.type == "RTG":
            if hours_dict.get(course.position, 0) < MIN_HOURS:
                res["hours_reached"] = False
            else:
                res["hours_reached"] = True

        try:
            WaitingListEntry.objects.get(user=request.user, course=course)
            res["entered"] = True
            if course.type == "RTG":
                n_rtg += 1
        except WaitingListEntry.DoesNotExist:
            res["entered"] = False

        courses_dict[course] = res

    return render(
        request,
        "lists/overview.html",
        {
            "courses": courses_dict,
            "error": error,
            "rating_reached": n_rtg >= 1,
            "min_hours": MIN_HOURS
        },
    )


@login_required
def join_leave_list(request, course_id):
    course = get_object_or_404(Course, pk=course_id)
    try:
        entry = WaitingListEntry.objects.get(user=request.user, course=course)
        entry.delete()
    except WaitingListEntry.DoesNotExist:
        # Check if user is already in another RTG course waiting list
        if course.type == "RTG" and WaitingListEntry.objects.filter(
                user=request.user, course__type="RTG"
        ).exists():
            # User is already in a rating course waiting list, redirect with error message
            messages.error(
                request,
                "You are already on the waiting list for a rating course. You can only join one rating course at a time."
            )
            return HttpResponseRedirect(reverse("lists:view_lists"))

        if course.min_rating <= request.user.userdetail.rating <= course.max_rating:
            if course.type == "RTG":
                try:
                    twr_s1, twr_s2, app_s3 = get_connections(request.user)
                    if (course.position == "TWR"):
                        if twr_s1 >= MIN_HOURS:
                            WaitingListEntry.objects.create(
                                user=request.user, course=course
                            )
                    elif (course.position == "APP"):
                        if twr_s2 >= MIN_HOURS:
                            WaitingListEntry.objects.create(
                                user=request.user, course=course
                            )
                    elif (course.position == "CTR"):
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
def mentor_view(request):
    courses = request.user.mentored_courses.all()

    total_waiting = 0
    rtg_waiting = 0
    edmt_waiting = 0
    fam_waiting = 0

    course_list = []

    for course in courses:
        if course.type == "RTG":
            waiting_entries = WaitingListEntry.objects.filter(
                course=course
            ).order_by('date_added')  # Sort by join date, oldest first
            rtg_waiting += waiting_entries.filter(activity__gte=ACTIVITY_MIN).count()
        else:
            waiting_entries = WaitingListEntry.objects.filter(course=course)
            if course.type == "EDMT":
                edmt_waiting += waiting_entries.count()
            elif course.type == "FAM":
                fam_waiting += waiting_entries.count()

        total_waiting += waiting_entries.count()

        waiting_list = []
        for entry in waiting_entries:
            first_initial = entry.user.first_name[0] if entry.user.first_name else ""
            last_initial = entry.user.last_name[0] if entry.user.last_name else ""

            waiting_list.append({
                'id': entry.id,
                'name': f"{entry.user.first_name} {entry.user.last_name}",
                'initials': f"{first_initial}{last_initial}",
                'vatsim_id': entry.user.username,
                'activity': round(entry.activity, 1)
            })

        course_list.append({
            'id': course.id,
            'name': course.name,
            'position': course.position,
            'position_display': course.get_position_display(),
            'type': course.type,
            'type_display': course.get_type_display(),
            'waiting_count': len(waiting_list),
            'waiting_list': waiting_list
        })

    course_list.sort(key=lambda x: (x['type'], x['position']))

    course_list_json = mark_safe(json.dumps(course_list))

    context = {
        'course_list_json': course_list_json,
        'total_waiting': total_waiting,
        'rtg_waiting': rtg_waiting,
        'edmt_waiting': edmt_waiting,
        'fam_waiting': fam_waiting,
        'activity_min': ACTIVITY_MIN
    }

    return render(request, 'lists/mentor.html', context)


@mentor_required
def start_training(request, waitlist_entry_id):
    entry = get_object_or_404(WaitingListEntry, pk=waitlist_entry_id)
    if request.user not in entry.course.mentors.all():
        return HttpResponseRedirect(reverse("lists:mentor_view"))

    if entry.activity < ACTIVITY_MIN and entry.course.type == "RTG":
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


@mentor_required
def remove_trainee(request, waitlist_entry_id):
    entry = get_object_or_404(WaitingListEntry, pk=waitlist_entry_id)

    if request.user in entry.course.mentors.all():
        # Log the action
        log_admin_action(
            request.user,
            entry.course,
            CHANGE,
            f"Removed trainee {entry.user} ({entry.user.username}) from waiting list for insufficient activity",
        )
        entry.delete()

    return redirect('lists:mentor_view')


# Helper functions
@cached(cache=TTLCache(maxsize=1024, ttl=60 * 60))
def get_user_endorsements(user_id: int) -> set:
    from endorsements.helpers import get_tier1_endorsements
    return set(
        [
            end["position"]
            for end in get_tier1_endorsements()
            if end["user_cid"] == user_id
        ]
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


def get_cached_connections(user):
    if user.username in connections_cache:
        return connections_cache[user.username]
    return get_connections(user)


def enrol_into_required_moodles(user_id, course_ids: list):
    header = {"Authorization": f"Token {os.getenv('VATGER_API_KEY')}"}
    for course_id in course_ids:
        requests.get(
            f"http://vatsim-germany.org/api/moodle/course/{course_id}/user/{user_id}/enrol",
            headers=header,
        )


def inform_user_course_start(vatsim_id: int, course_name: str):
    from overview.helpers import inform_user_course_start as helper_inform
    helper_inform(vatsim_id, course_name)