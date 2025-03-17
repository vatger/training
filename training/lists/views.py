from cachetools import TTLCache, cached
import requests

from django.contrib.auth.decorators import login_required
from django.shortcuts import get_object_or_404, render, HttpResponseRedirect, reverse

from .models import Course, WaitingListEntry
import os
from dotenv import load_dotenv

load_dotenv()


min_hours = 25
activity_min = 8


def enrol_into_required_moodles(user_id, course_ids: list):
    header = {"Authorization": f"Token {os.getenv("VATGER_API_KEY")}"}
    for course_id in course_ids:
        requests.get(
            f"https://vatsim-germany.org/api/moodle/course/{course_id}/user/{user_id}/enrol",
            headers=header,
        )


@cached(cache=TTLCache(maxsize=float("inf"), ttl=60 * 60))
def get_connections(user):
    api_url = f"https://api.vatsim.net/api/ratings/{user.username}/atcsessions"
    try:
        res = requests.get(api_url).json()
        response = res["results"]
    except:
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
    return twr_s1 / 60, twr_s2 / 60, app_s3 / 60


@login_required
def view_lists(request):
    # make sure user is not currently active_trainee
    courses = Course.objects.filter(
        min_rating__lte=request.user.userdetail.rating,
        max_rating__gte=request.user.userdetail.rating,
    ).exclude(active_trainees=request.user)
    entered = {}

    try:
        twr_s1, twr_s2, app_s3 = get_connections(request.user)
        error = False
    except:
        twr_s1, twr_s2, app_s3 = 26, 0, 0
        error = True

    for course in courses:
        if course.type == "RTG":
            match course.position:
                case "TWR":
                    if twr_s1 < min_hours:
                        continue
                case "APP":
                    if twr_s2 < min_hours:
                        continue
                case "CTR":
                    if app_s3 < min_hours:
                        continue
        try:
            WaitingListEntry.objects.get(user=request.user, course=course)
            entered[course] = True
        except WaitingListEntry.DoesNotExist:
            entered[course] = False

    return render(
        request, "lists/overview.html", {"courses": courses, "entered": entered}
    )


@login_required
def join_leave_list(request, course_id):
    course = get_object_or_404(Course, pk=course_id)
    try:
        entry = WaitingListEntry.objects.get(user=request.user, course=course)
        entry.delete()
    except WaitingListEntry.DoesNotExist:
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


@login_required
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


@login_required
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
    return HttpResponseRedirect(reverse("lists:mentor_view"))
