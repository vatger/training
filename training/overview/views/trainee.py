from django.contrib.admin.models import CHANGE
from django.contrib.auth.models import User
from django.shortcuts import redirect, get_object_or_404
from training.helpers import log_admin_action
from training.permissions import mentor_required

from lists.models import Course
from overview.models import TraineeClaim, TraineeRemark


@mentor_required
def claim_trainee(request, trainee_id, course_id):
    course = get_object_or_404(Course, id=course_id)
    if request.user not in course.mentors.all():
        return redirect("overview:overview")
    try:
        # If trainee is already claimed by someone else, continue without doing anything
        obj = TraineeClaim.objects.get(trainee_id=trainee_id, course_id=course_id)
        if obj.mentor != request.user:
            return redirect("overview:overview")
    except TraineeClaim.DoesNotExist:
        pass
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


@mentor_required
def remove_trainee(request, trainee_id, course_id):
    try:
        course = Course.objects.get(id=course_id)
        trainee = User.objects.get(id=trainee_id)
        if request.user in course.mentors.all():
            course.active_trainees.remove(trainee_id)
            log_admin_action(
                request.user,
                course,
                CHANGE,
                f"Removed trainee {trainee} ({trainee.username}) from active",
            )
    except Course.DoesNotExist:
        pass
    return redirect("overview:overview")


@mentor_required
def finish_course(request, trainee_id, course_id):
    course = get_object_or_404(Course, id=course_id)
    trainee = get_object_or_404(User, id=trainee_id)
    if request.user not in course.mentors.all():
        return redirect("overview:overview")

    from overview.helpers.trainee import complete_trainee_course

    complete_trainee_course(request.user, trainee, course)

    return redirect("overview:overview")


@mentor_required
def update_remark(request, trainee_id, course_id):
    """
    View to handle updating or creating trainee remarks by mentors.
    """
    if request.method == "POST":
        trainee = get_object_or_404(User, id=trainee_id)
        course = get_object_or_404(Course, id=course_id)

        # Make sure the mentor has access to this course
        if request.user not in course.mentors.all() and not request.user.is_superuser:
            return redirect("overview:overview")

        remark_text = request.POST.get("remark", "").strip()

        # Try to get an existing remark or create a new one
        try:
            remark = TraineeRemark.objects.get(trainee=trainee, course=course)

            # If remark is empty, set remark to null to ensure last_updated
            # doesn't display in the UI when there's no content
            if not remark_text:
                remark.remark = None
            else:
                remark.remark = remark_text

            remark.last_updated_by = request.user
            remark.save()
        except TraineeRemark.DoesNotExist:
            # Only create a new remark if there's content
            if remark_text:
                TraineeRemark.objects.create(
                    trainee=trainee,
                    course=course,
                    remark=remark_text,
                    last_updated_by=request.user,
                )

        # Log the action
        action_type = "Updated" if remark_text else "Removed"
        log_admin_action(
            request.user,
            course,
            CHANGE,
            f"{action_type} remark for trainee {trainee} ({trainee.username}) in course {course}",
        )

    # Redirect back to the overview page
    return redirect("overview:overview")
