from django.contrib.admin.models import CHANGE
from django.contrib.auth.models import User
from django.shortcuts import render, redirect, get_object_or_404
from training.helpers import log_admin_action
from training.permissions import mentor_required

from lists.models import Course
from overview.forms import AddUserForm


@mentor_required
def manage_mentors(request, course_id):
    course = get_object_or_404(Course, id=course_id)
    if request.user not in course.mentors.all():
        return redirect("overview:overview")

    if request.method == "POST":
        form = AddUserForm(request.POST)
        if form.is_valid():
            course_id = form.cleaned_data["course_id"]
            username = form.cleaned_data["username"]
            course = get_object_or_404(Course, id=course_id)

            try:
                user = User.objects.get(username=username)
                if user not in course.mentors.all():
                    if course.mentor_group is not None:
                        if user.groups.filter(id=course.mentor_group.id).exists():
                            course.mentors.add(user)
                            log_admin_action(
                                request.user,
                                course,
                                CHANGE,
                                f"Added mentor {user} ({user.username}) to course {course}",
                            )
                    else:
                        course.mentors.add(user)
            except User.DoesNotExist:
                form.add_error("username", "User not found.")

    mentors = course.mentors.all()

    return render(
        request, "overview/course_mentors.html", {"mentors": mentors, "course": course}
    )


@mentor_required
def remove_mentor(request, course_id, mentor_id):
    course = get_object_or_404(Course, id=course_id)
    mentor = get_object_or_404(User, id=mentor_id)
    if request.user in course.mentors.all():
        course.mentors.remove(mentor)
        log_admin_action(
            request.user,
            course,
            CHANGE,
            f"Removed mentor {mentor} ({mentor.username}) from course {course}",
        )
    return redirect("overview:manage_mentors", course_id=course_id)
