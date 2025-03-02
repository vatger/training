import os
from datetime import datetime, timezone

import requests
from cachetools import TTLCache, cached
from django.contrib.auth.decorators import login_required
from django.contrib.auth.models import User
from django.shortcuts import redirect, get_object_or_404
from django.shortcuts import render
from dotenv import load_dotenv
from lists.models import Course
from logs.models import Log
from overview.models import TraineeClaim

from .forms import AddUserForm

load_dotenv()


@cached(cache=TTLCache(maxsize=1024, ttl=60 * 10))
def get_solos():
    eud_header = {
        "X-API-KEY": os.getenv("CORE_API"),
        "Accept": "application/json",
        "User-Agent": "VATGER",
    }
    solos = requests.get(
        "https://core.vateud.net/api/facility/endorsements/solo", headers=eud_header
    ).json()["data"]
    res = []
    for solo in solos:
        expiry_date = datetime.fromisoformat(solo["expiry"].replace("Z", "+00:00"))
        created_date = datetime.fromisoformat(solo["created_at"].replace("Z", "+00:00"))
        remaining_days = (expiry_date - datetime.now(timezone.utc)).days
        delta = solo["max_days"] - (expiry_date - created_date).days
        res.append(
            {
                "user_cid": solo["user_cid"],
                "position": solo["position"],
                "expiry": expiry_date,
                "remaining_days": remaining_days,
                "delta": delta,
            }
        )
    return res


@login_required
def overview(request):
    if request.method == "POST":
        form = AddUserForm(request.POST)
        if form.is_valid():
            course_id = form.cleaned_data["course_id"]
            username = form.cleaned_data["username"]
            course = get_object_or_404(Course, id=course_id)

            try:
                user = User.objects.get(username=username)
                if user not in course.active_trainees.all():
                    course.active_trainees.add(user)
            except User.DoesNotExist:
                form.add_error("username", "User not found.")

        return redirect("overview:overview")

    courses = request.user.mentored_courses.all()
    solos = get_solos()
    res = {}
    for course in courses:
        course_trainees = {}
        trainees = course.active_trainees.all()
        for trainee in trainees:
            claim = TraineeClaim.objects.filter(
                mentor=request.user, trainee=trainee, course=course
            ).exists()
            solo = [
                solo
                for solo in solos
                if solo["position"] == course.solo_station
                and solo["user_cid"] == trainee.username
            ]

            if claim:
                claimer = TraineeClaim.objects.get(
                    mentor=request.user, trainee=trainee, course=course
                ).mentor
            course_trainees[trainee] = {
                "logs": Log.objects.filter(trainee=trainee, course=course).order_by(
                    "session_date"
                ),
                "claimed": claim,
                "claimed_by": (
                    claimer.first_name + " " + claimer.last_name if claim else None
                ),
                "solo": (
                    f"{solo[0]["remaining_days"]}/{solo[0]["delta"]}" if solo else "NA"
                ),
            }
            try:
                next_step = course_trainees[trainee]["logs"].last().next_step
                date_last = course_trainees[trainee]["logs"].last().session_date
            except:
                next_step = ""
                date_last = None
            course_trainees[trainee]["next_step"] = next_step
            course_trainees[trainee]["date_last"] = date_last
        res[course] = course_trainees
    return render(request, "overview/overview.html", {"overview": res})


@login_required
def claim_trainee(request, trainee_id, course_id):
    try:
        obj = TraineeClaim.objects.get(
            mentor=request.user, trainee_id=trainee_id, course_id=course_id
        )
        obj.delete()
    except TraineeClaim.DoesNotExist:
        if request.user.mentored_courses.filter(id=course_id).exists():
            TraineeClaim.objects.create(
                mentor=request.user, trainee_id=trainee_id, course_id=course_id
            )
    return redirect("overview:overview")


# todo: mentor only
def remove_trainee(request, trainee_id, course_id):
    try:
        course = Course.objects.get(id=course_id)
        course.active_trainees.remove(trainee_id)
    except Course.DoesNotExist:
        pass
    return redirect("overview:overview")
