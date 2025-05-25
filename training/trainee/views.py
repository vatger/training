import os

import requests
from cachetools import cached, TTLCache
from connect.views import mentor_groups
from django.contrib.auth.decorators import login_required
from django.contrib.auth.models import User
from django.shortcuts import get_object_or_404, HttpResponseRedirect, reverse, redirect
from django.shortcuts import render
from dotenv import load_dotenv
from endorsements.helpers import get_tier1_endorsements, get_tier2_endorsements
from endorsements.models import EndorsementActivity
from endorsements.views import min_hours_required
from familiarisations.helpers import get_familiarisations
from lists.models import Course
from logs.models import Log
from overview.helpers.trainee import get_course_completion
from trainee.forms import UserDetailForm
from training.permissions import mentor_required

from .forms import CommentForm

load_dotenv()


@cached(cache=TTLCache(maxsize=1024, ttl=60 * 60 * 24))
def get_course_name(course_id: int) -> str:
    header = {"Authorization": f"Token {os.getenv('VATGER_API_KEY')}"}
    link = f"http://vatsim-germany.org/api/moodle/course/{course_id}"
    r = requests.get(link, headers=header)
    if r.status_code == 200:
        r = r.json()
        return r["displayname"]
    else:
        return ""


def split_active_inactive(logs, courses, trainee):
    active = {}
    inactive = {}
    active_courses = set(trainee.active_courses.all())

    non_active = courses - active_courses
    for course in active_courses:
        active[course] = logs.filter(course=course).order_by("-session_date")

    for course in non_active:
        inactive[course] = logs.filter(course=course).order_by("-session_date")

    return active, inactive


def get_moodles(user) -> list:
    active_courses = user.active_courses.all()
    moodles = []
    for course in active_courses:
        for moodle_id in course.moodle_course_ids:
            link = f"https://moodle.vatsim-germany.org/course/view.php?id={moodle_id}"
            passed = get_course_completion(user.username, moodle_id)
            moodles.append(
                {
                    "course": course.name,
                    "passed": passed,
                    "id": moodle_id,
                    "link": link,
                    "name": get_course_name(moodle_id),
                }
            )
    return moodles


@login_required
def home(request):
    logs = Log.objects.filter(trainee=request.user).order_by("-session_date")
    # Get all courses from the logs
    courses = set(Course.objects.filter(log__in=logs))

    active, inactive = split_active_inactive(logs, courses, request.user)

    # Get required Moodle courses
    moodles = get_moodles(request.user)
    fams = get_familiarisations(request.user.username)

    return render(
        request,
        "trainee/dashboard.html",
        {"active": active, "inactive": inactive, "moodles": moodles, "fams": fams},
    )


@mentor_required
def mentor_view(request, vatsim_id: int):
    trainee = get_object_or_404(User, username=vatsim_id)
    courses = request.user.mentored_courses.all()
    if request.user.is_superuser:
        courses = Course.objects.all()

    if (
        not request.user.groups.filter(name__in=mentor_groups).exists()
        and not request.user.is_superuser
    ):
        return redirect("/")
    # Get all logs for the trainee that are in the courses
    logs = Log.objects.filter(trainee=trainee, course__in=courses).order_by(
        "-session_date"
    )
    # Get all courses from the logs
    courses = set(Course.objects.filter(log__in=logs))
    active, inactive = split_active_inactive(logs, courses, trainee)

    comments = trainee.comments.all().order_by("-date_added")
    if request.method == "POST":
        form = CommentForm(request.POST)
        if form.is_valid():
            text = form.cleaned_data["text"]
            author = request.user
            trainee.comments.create(text=text, author=author)
            return HttpResponseRedirect(
                reverse("trainee:mentor_view", args=[trainee.username])
            )
    else:
        form = CommentForm()

    moodles = get_moodles(trainee)
    fams = get_familiarisations(trainee.username)

    # Get all courses the mentor can assign
    available_courses = Course.objects.filter(mentors=request.user)
    if request.user.is_superuser:
        available_courses = Course.objects.all()
    # Exclude courses the trainee is already in
    available_courses = available_courses.exclude(active_trainees=trainee)

    tier_1 = get_tier1_endorsements()
    tier_1 = [t1 for t1 in tier_1 if t1["user_cid"] == vatsim_id]
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

    tier_2 = get_tier2_endorsements()
    tier_2 = [t2 for t2 in tier_2 if t2["user_cid"] == vatsim_id]

    return render(
        request,
        "trainee/mentor.html",
        {
            "trainee": trainee,
            "active": active,
            "inactive": inactive,
            "comments": comments,
            "form": form,
            "moodles": moodles,
            "fams": fams,
            "available_courses": available_courses,
            "tier_1": res_t1,
            "tier_2": tier_2,
            "min_hours": min_hours_required,
            "half_min_hours": min_hours_required // 2,
        },
    )


@mentor_required
def find_user(request):
    if request.method == "POST":
        user_form = UserDetailForm(request.POST)
        if user_form.is_valid():
            user_id = user_form.cleaned_data["user_id"]
            # Check if the user exists
            if User.objects.filter(username=user_id).exists():
                # Redirect to the user_detail view
                return HttpResponseRedirect(
                    reverse("trainee:mentor_view", args=[user_id])
                )
            else:
                user_form.add_error("user_id", "User not found.")
    else:
        user_form = UserDetailForm()

    return render(request, "trainee/find_user.html", {"user_form": user_form})
