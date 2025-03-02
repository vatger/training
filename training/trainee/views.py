from django.shortcuts import render
from django.contrib.auth.decorators import login_required

from lists.models import Course
from logs.models import Log


@login_required
def home(request):
    active = {}
    inactive = {}
    logs = Log.objects.filter(trainee=request.user)
    # Get all courses from the logs
    courses = set(Course.objects.filter(log__in=logs))
    active_courses = set(request.user.active_courses.all())

    non_active = courses - active_courses
    for course in active_courses:
        active[course] = logs.filter(course=course).order_by("-session_date")

    for course in non_active:
        inactive[course] = logs.filter(course=course).order_by("-session_date")

    return render(
        request, "trainee/home.html", {"active": active, "inactive": inactive}
    )
