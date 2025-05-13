from django.contrib.admin.models import CHANGE
from django.contrib.auth.models import User
from django.shortcuts import get_object_or_404
from django.shortcuts import render, redirect
from overview.helpers.course import get_solos
from overview.helpers.trainee import inform_user_course_start, get_course_completion
from training.helpers import log_admin_action
from training.permissions import mentor_required

from lists.models import Course, WaitingListEntry
from lists.views import enrol_into_required_moodles
from logs.models import Log
from overview.forms import AddUserForm
from overview.models import TraineeClaim, TraineeRemark


@mentor_required
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
                    enrol_into_required_moodles(user.username, course.moodle_course_ids)
                    inform_user_course_start(int(user.username), course.name)
                    WaitingListEntry.objects.filter(user=user, course=course).delete()

                    log_admin_action(
                        request.user,
                        course,
                        CHANGE,
                        f"Added trainee {user} ({user.username}) to course {course}",
                    )
            except User.DoesNotExist:
                form.add_error("username", "User not found.")

        return redirect("overview:overview")

    # Get all courses mentored by current user
    courses = request.user.mentored_courses.all()
    courses = sorted(courses, key=lambda course: str(course))

    solos = get_solos()
    res = {}

    # Counters for summary metrics
    active_trainees_count = 0
    claimed_trainees_count = 0
    waiting_trainees_count = 0

    for course in courses:
        course_trainees = {}
        trainees = course.active_trainees.all()
        active_trainees_count += trainees.count()

        for trainee in trainees:
            # Check if trainee is claimed
            claim = TraineeClaim.objects.filter(trainee=trainee, course=course)
            if claim.exists():
                if claim[0].mentor == request.user:
                    claimed_trainees_count += 1

            # Moodle check for EDMT and GST
            moodle_completed = True
            if course.type != "RTG":
                for moodle_course_id in course.moodle_course_ids:
                    moodle_completed = moodle_completed and get_course_completion(
                        trainee.username, moodle_course_id
                    )

            # Check solo status
            solo = [
                solo
                for solo in solos
                if solo["position"] == course.solo_station
                and solo["user_cid"] == int(trainee.username)
                and solo["remaining_days"] >= 0
            ]
            solo_info = (
                f"{solo[0]['remaining_days']}/{solo[0]['delta']}"
                if solo and solo[0]["remaining_days"] >= 0
                else "Add Solo"
            )
            if solo:
                solo[0]["solo_info"] = solo_info if solo else "Add Solo"
                solo[0]["max_days"] = solo[0].get("max_days", 0)
                solo[0]["position_days"] = solo[0].get("position_days", 0)

            # Get the mentor who claimed this trainee
            if claim.exists():
                try:
                    claimer = TraineeClaim.objects.get(
                        trainee=trainee, course=course
                    ).mentor
                except TraineeClaim.MultipleObjectsReturned:
                    print(f"Multiple claims for trainee {trainee} in course {course}")
                    claimer = (
                        TraineeClaim.objects.filter(trainee=trainee, course=course)
                        .first()
                        .mentor
                    )

            # Get training logs for this trainee in this course
            logs = Log.objects.filter(trainee=trainee, course=course).order_by(
                "session_date"
            )

            # Get trainee remark for this course
            try:
                remark = TraineeRemark.objects.get(trainee=trainee, course=course)
                remark_text = remark.remark
                remark_updated = remark.last_updated
                remark_updated_by = remark.last_updated_by
            except TraineeRemark.DoesNotExist:
                remark_text = ""
                remark_updated = None
                remark_updated_by = None

            course_trainees[trainee] = {
                "logs": logs,
                "claimed": claim.exists(),
                "claimed_by": (
                    claimer.first_name + " " + claimer.last_name
                    if claim.exists()
                    else None
                ),
                "solo": solo[0] if solo else "Add Solo",
                "moodle": moodle_completed,
                "remark": remark_text,
                "remark_updated": remark_updated,
                "remark_updated_by": remark_updated_by,
            }

            # Get the next step and last training date
            try:
                next_step = logs.last().next_step
                date_last = logs.last().session_date
            except:
                next_step = ""
                date_last = None

            course_trainees[trainee]["next_step"] = next_step
            course_trainees[trainee]["date_last"] = date_last

        res[course] = course_trainees

    # Count waiting list entries
    for course in courses:
        if course.type == "RTG":
            waiting_trainees_count += WaitingListEntry.objects.filter(
                course=course, activity__gte=10
            ).count()
        else:
            waiting_trainees_count += WaitingListEntry.objects.filter(
                course=course
            ).count()

    return render(
        request,
        "overview/index.html",
        {
            "overview": res,
            "coursedict": res,
            "courses": courses,
            "active_trainees_count": active_trainees_count,
            "claimed_trainees_count": claimed_trainees_count,
            "waiting_trainees_count": waiting_trainees_count,
        },
    )
