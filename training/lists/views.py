import os

import requests
from cachetools import TTLCache, cached
from django.contrib.admin.models import CHANGE
from django.shortcuts import (
    get_object_or_404,
    render,
    HttpResponseRedirect,
    reverse,
    redirect,
)
from dotenv import load_dotenv
from endorsements.helpers import get_tier1_endorsements
from familiarisations.models import Familiarisation
from overview.helpers import inform_user_course_start
from familiarisations.models import Familiarisation
from training.helpers import log_admin_action
from training.permissions import mentor_required
from django.contrib.auth.decorators import login_required

from .models import Course, WaitingListEntry

load_dotenv()


min_hours = 25
activity_min = 8  # Policy says 10 hours, but we are more lenient here


def enrol_into_required_moodles(user_id, course_ids: list):
    header = {"Authorization": f"Token {os.getenv("VATGER_API_KEY")}"}
    for course_id in course_ids:
        requests.get(
            f"http://vatsim-germany.org/api/moodle/course/{course_id}/user/{user_id}/enrol",
            headers=header,
        )


@cached(cache=TTLCache(maxsize=1024, ttl=60 * 60))
def get_user_endorsements(user_id: int) -> set:
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
    
    # Do not show familarisation courses if user already has the familiarisation
    familiarisations = list(
        Familiarisation.objects.filter(user=request.user).values_list(
            "sector", flat=True
        )
    )

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
        # Familiarisation check
        if course.type == "FAM":
            if course.familiarisation_sector.id in familiarisations:
                continue
        res = {"course": course, "hours_reached": True}
        if course.type == "RTG":
            if hours_dict[course.position] < min_hours:
                res["hours_reached"] = False
            else:
                res["hours_reached"] = True
        try:
            WaitingListEntry.objects.get(user=request.user, course=course)
            res["entered"] = True
            res["rtg_limit_reached"] = False
            if course.type == "RTG":
                n_rtg += 1
        except WaitingListEntry.DoesNotExist:
            res["entered"] = False
        courses_dict[course] = res
    return render(
        request,
        "lists/overview.html",
        {"courses": courses_dict, "error": error, "rating_reached": n_rtg >= 1},
    )


@login_required
def join_leave_list(request, course_id):
    # Temporary diabling of course joining
    return redirect("lists:view_lists")

    course = get_object_or_404(Course, pk=course_id)
    try:
        entry = WaitingListEntry.objects.get(user=request.user, course=course)
        entry.delete()
    except WaitingListEntry.DoesNotExist:
        n_rtg = WaitingListEntry.objects.filter(
            user=request.user, course__type="RTG"
        ).count()
        if course.type == "RTG" and n_rtg >= 1:
            return HttpResponseRedirect(reverse("lists:view_lists"))
        if course.min_rating <= request.user.userdetail.rating <= course.max_rating:
            if course.type == "RTG":
                try:
                    twr_s1, twr_s2, app_s3 = get_connections(request.user)
                    match course.position:
                        case "TWR":
                            if twr_s1 >= min_hours:
                                WaitingListEntry.objects.create(
                                    user=request.user, course=course
                                )
                        case "APP":
                            if twr_s2 >= min_hours:
                                WaitingListEntry.objects.create(
                                    user=request.user, course=course
                                )
                        case "CTR":
                            if app_s3 >= min_hours:
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
    res = {}
    courses = request.user.mentored_courses.all()
    for course in courses:
        if course.type == "RTG":
            res[course] = list(
                WaitingListEntry.objects.filter(
                    course=course, activity__gte=activity_min
                )
            )
        else:
            res[course] = list(WaitingListEntry.objects.filter(course=course))
    return render(request, "lists/mentor.html", {"coursedict": res})


@mentor_required
def start_training(request, waitlist_entry_id):
    entry = get_object_or_404(WaitingListEntry, pk=waitlist_entry_id)
    if request.user not in entry.course.mentors.all():
        return HttpResponseRedirect(reverse("lists:mentor_view"))
    if entry.activity < activity_min and entry.course.type == "RTG":
        return HttpResponseRedirect(reverse("lists:mentor_view"))
    # Add user to active_trainees
    entry.course.active_trainees.add(entry.user)
    entry.delete()
    enrol_into_required_moodles(entry.user.username, entry.course.moodle_course_ids)
    inform_user_course_start(int(entry.user.username), entry.course.name)
    log_admin_action(
        request.user,
        entry.course,
        CHANGE,
        f"Added trainee {entry.user} ({entry.user.username}) to course {entry.course}",
    )
    return HttpResponseRedirect(reverse("lists:mentor_view"))
