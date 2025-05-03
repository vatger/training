from datetime import datetime, timezone

import requests
from django.conf import settings
from django.contrib.admin.models import CHANGE
from django.contrib.auth.models import User
from django.core.exceptions import MultipleObjectsReturned
from django.shortcuts import redirect, get_object_or_404
from django.shortcuts import render
from dotenv import load_dotenv
from training.eud_header import eud_header
from training.helpers import log_admin_action
from training.permissions import mentor_required

from familiarisations.models import Familiarisation, FamiliarisationSector
from lists.models import Course, WaitingListEntry
from lists.views import enrol_into_required_moodles
from logs.models import Log
from overview.models import TraineeClaim, TraineeRemark
from .forms import AddUserForm
from .helpers import (
    get_course_completion,
    get_core_theory_passed,
    CoreState,
    assign_core_test,
    inform_user_course_start,
)

load_dotenv()


# @cached(cache=TTLCache(maxsize=1024, ttl=60 * 10))
def get_solos():
    if settings.USE_CORE_MOCK:
        solos = [
            {
                "id": 1,
                "user_cid": 1601613,
                "instructor_cid": 1439797,
                "position": "EDDL_APP",
                "expiry": "2025-05-13T00:00:00.000000Z",
                "max_days": 74,
                "facility": 9,
                "created_at": "2025-03-05T02:10:40.000000Z",
                "updated_at": "2025-04-07T19:47:47.000000Z",
            },
        ]
    else:
        solos = requests.get(
            "https://core.vateud.net/api/facility/endorsements/solo", headers=eud_header
        ).json()["data"]
    res = []
    for solo in solos:
        expiry_date = datetime.fromisoformat(solo["expiry"].replace("Z", "+00:00"))
        created_date = datetime.fromisoformat(solo["created_at"].replace("Z", "+00:00"))
        remaining_days = (expiry_date.date() - datetime.now(timezone.utc).date()).days
        delta = solo["max_days"] - (expiry_date - created_date).days
        res.append(
            {
                "id": solo["id"],
                "user_cid": solo["user_cid"],
                "position": solo["position"],
                "expiry": expiry_date,
                "remaining_days": remaining_days,
                "delta": delta,
            }
        )
    return res


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
    course.active_trainees.remove(trainee_id)
    log_admin_action(
        request.user,
        course,
        CHANGE,
        f"Finished trainee {trainee} ({trainee.username}), added endorsements",
    )
    if course.endorsement_groups.all():
        endorsements = requests.get(
            "https://core.vateud.net/api/facility/endorsements/tier-1",
            headers=eud_header,
        ).json()["data"]

    for endorsement_group in course.endorsement_groups.all():
        if [
            endorsement
            for endorsement in endorsements
            if endorsement["user_cid"] == int(trainee.username)
            and endorsement["position"] == endorsement_group.name
        ]:
            continue

        requests.post(
            "https://core.vateud.net/api/facility/endorsements/tier-1",
            headers=eud_header,
            json={
                "user_cid": int(trainee.username),
                "position": endorsement_group.name,
                "instructor_cid": request.user.username,
            },
        )

    if course.type == "RTG" and course.position == "CTR":
        fir = course.mentor_group.name[:4]  # Gepfuscht, aber wcyd
        sectors = FamiliarisationSector.objects.filter(fir=fir)
        for sector in sectors:
            if not Familiarisation.objects.filter(user=trainee, sector=sector).exists():
                Familiarisation.objects.create(user=trainee, sector=sector)
                log_admin_action(
                    request.user,
                    course,
                    CHANGE,
                    f"Added familiarisation {sector} ({sector.name}) to trainee {trainee} ({trainee.username})",
                )
    elif course.type == "FAM":
        # Create familiarisation if does not exist
        if course.familiarisation_sector is None:
            return redirect("overview:overview")
        _, _ = Familiarisation.objects.get_or_create(
            user=trainee, sector=course.familiarisation_sector
        )
    return redirect("overview:overview")


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


@mentor_required
def assign_core_test_view(request, vatsim_id: int, course_id: int):
    course = get_object_or_404(Course, id=course_id)
    if request.user not in course.mentors.all():
        return redirect("overview:overview")
    if get_core_theory_passed(vatsim_id, course.position) != CoreState.NOT_ASSIGNED:
        return redirect("overview:overview")
    assign_core_test(request.user.username, vatsim_id, course.position)
    return redirect("overview:overview")


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

            # Get the mentor who claimed this trainee
            if claim.exists():
                try:
                    claimer = TraineeClaim.objects.get(
                        trainee=trainee, course=course
                    ).mentor
                except MultipleObjectsReturned:
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
        "overview/overview.html",
        {
            "overview": res,
            "coursedict": res,
            "courses": courses,
            "active_trainees_count": active_trainees_count,
            "claimed_trainees_count": claimed_trainees_count,
            "waiting_trainees_count": waiting_trainees_count,
        },
    )


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
