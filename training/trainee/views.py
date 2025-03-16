from typing import Optional

from django.shortcuts import render
from django.contrib.auth.decorators import login_required
from django.contrib.auth.models import User
from django.shortcuts import get_object_or_404

from lists.models import Course
from logs.models import Log


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


@login_required
def home(request):
    logs = Log.objects.filter(trainee=request.user)
    # Get all courses from the logs
    courses = set(Course.objects.filter(log__in=logs))

    active, inactive = split_active_inactive(logs, courses, request.user)

    return render(
        request, "trainee/home.html", {"active": active, "inactive": inactive}
    )


@login_required
def mentor_view(request, vatsim_id: int):
    trainee = get_object_or_404(User, username=vatsim_id)
    courses = request.user.mentored_courses.all()
    if request.user.is_superuser:
        courses = Course.objects.all()
    # Get all logs for the trainee that are in the courses
    logs = Log.objects.filter(trainee=trainee, course__in=courses).order_by(
        "-session_date"
    )
    # Get all courses from the logs
    courses = set(Course.objects.filter(log__in=logs))
    active, inactive = split_active_inactive(logs, courses, trainee)

    return render(
        request, "trainee/home.html", {"active": active, "inactive": inactive}
    )
