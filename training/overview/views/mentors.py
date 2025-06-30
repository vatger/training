from django.contrib.admin.models import CHANGE
from django.contrib.auth.models import User
from django.http import JsonResponse
from django.shortcuts import get_object_or_404
from django.views.decorators.http import require_http_methods
from training.helpers import log_admin_action
from training.permissions import mentor_required

from lists.models import Course


@mentor_required
def manage_mentors(request, course_id):
    course = get_object_or_404(Course, id=course_id)
    if request.user not in course.mentors.all():
        return JsonResponse(
            {"error": "You are not authorized to manage this course."}, status=403
        )

    if request.method == "GET":
        mentors = course.mentors.all()
        mentors_data = []

        for mentor in mentors:
            mentors_data.append(
                {
                    "id": mentor.id,
                    "username": mentor.username,
                    "name": f"{mentor.first_name} {mentor.last_name}",
                    "initials": (
                        f"{mentor.first_name[:1]}{mentor.last_name[:1]}"
                        if mentor.first_name and mentor.last_name
                        else mentor.username[:2].upper()
                    ),
                    "email": mentor.email,
                }
            )

        course_data = {
            "id": course.id,
            "name": course.name,
            "position": course.position,
            "mentor_group_name": (
                course.mentor_group.name if course.mentor_group else None
            ),
        }

        return JsonResponse(
            {
                "course": course_data,
                "mentors": mentors_data,
            }
        )

    elif request.method == "POST":
        # Handle adding a new mentor
        username = request.POST.get("username")
        if not username:
            return JsonResponse({"success": False, "error": "Username is required."})

        try:
            user = User.objects.get(username=username)
            if user in course.mentors.all():
                return JsonResponse(
                    {
                        "success": False,
                        "error": "User is already a mentor for this course.",
                    }
                )

            # Check if user is in the required mentor group
            if course.mentor_group is not None:
                if not user.groups.filter(id=course.mentor_group.id).exists():
                    return JsonResponse(
                        {
                            "success": False,
                            "error": f"User must be a member of the {course.mentor_group.name} group.",
                        }
                    )

            course.mentors.add(user)
            log_admin_action(
                request.user,
                course,
                CHANGE,
                f"Added mentor {user} ({user.username}) to course {course}",
            )

            return JsonResponse(
                {
                    "success": True,
                    "message": f"Successfully added {user.get_full_name()} as a mentor.",
                }
            )

        except User.DoesNotExist:
            return JsonResponse({"success": False, "error": "User not found."})
    return None


@mentor_required
@require_http_methods(["POST"])
def remove_mentor(request, course_id, mentor_id):
    course = get_object_or_404(Course, id=course_id)
    mentor = get_object_or_404(User, id=mentor_id)

    if request.user not in course.mentors.all():
        return JsonResponse(
            {"error": "You are not authorized to manage this course."}, status=403
        )

    if mentor in course.mentors.all():
        course.mentors.remove(mentor)
        log_admin_action(
            request.user,
            course,
            CHANGE,
            f"Removed mentor {mentor} ({mentor.username}) from course {course}",
        )
        return JsonResponse(
            {
                "success": True,
                "message": f"Successfully removed {mentor.get_full_name()} as a mentor.",
            }
        )
    else:
        return JsonResponse(
            {"success": False, "error": "User is not a mentor for this course."}
        )
