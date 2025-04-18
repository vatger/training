from datetime import datetime, timezone

import requests
from django.contrib.admin.models import CHANGE
from django.contrib.auth.decorators import login_required
from django.contrib.auth.models import User
from django.core.exceptions import MultipleObjectsReturned
from django.shortcuts import redirect, get_object_or_404
from django.shortcuts import render
from dotenv import load_dotenv
from familiarisations.models import Familiarisation, FamiliarisationSector, FIR
from lists.models import Course, WaitingListEntry
from lists.views import enrol_into_required_moodles
from logs.models import Log
from overview.models import TraineeClaim
from training.eud_header import eud_header
from training.helpers import log_admin_action

from .forms import AddUserForm, SoloForm
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

    courses = request.user.mentored_courses.all()
    courses = sorted(courses, key=lambda course: str(course))
    solos = get_solos()
    res = {}
    for course in courses:
        course_trainees = {}
        trainees = course.active_trainees.all()
        for trainee in trainees:
            claim = TraineeClaim.objects.filter(trainee=trainee, course=course).exists()

            # Moodle check for EDMT and GST
            moodle_completed = True
            if course.type != "RTG":
                for moodle_course_id in course.moodle_course_ids:
                    moodle_completed = moodle_completed and get_course_completion(
                        trainee.username, moodle_course_id
                    )

            solo = [
                solo
                for solo in solos
                if solo["position"] == course.solo_station
                and solo["user_cid"] == int(trainee.username)
            ]
            solo_info = (
                f"{solo[0]["remaining_days"]}/{solo[0]["delta"]}"
                if solo
                else "Add Solo"
            )
            if claim:
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
            course_trainees[trainee] = {
                "logs": Log.objects.filter(trainee=trainee, course=course).order_by(
                    "session_date"
                ),
                "claimed": claim,
                "claimed_by": (
                    claimer.first_name + " " + claimer.last_name if claim else None
                ),
                "solo": solo_info,
            }
            try:
                next_step = course_trainees[trainee]["logs"].last().next_step
                date_last = course_trainees[trainee]["logs"].last().session_date
            except:
                next_step = ""
                date_last = None
            course_trainees[trainee]["next_step"] = next_step
            course_trainees[trainee]["date_last"] = date_last
            course_trainees[trainee]["moodle"] = moodle_completed
        res[course] = course_trainees
    return render(request, "overview/overview.html", {"overview": res})


@login_required
def claim_trainee(request, trainee_id, course_id):
    course = get_object_or_404(Course, id=course_id)
    if request.user not in course.mentors.all():
        return redirect("overview:overview")
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


@login_required
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


@login_required
def add_solo(request, vatsim_id, course_id):
    course = get_object_or_404(Course, id=course_id)
    if course.type != "RTG":
        return redirect("overview:overview")

    core_passed = get_core_theory_passed(int(vatsim_id), course.position)
    moodle_completed = True
    for course_id in course.moodle_course_ids:
        moodle_completed = moodle_completed and get_course_completion(
            int(vatsim_id), course_id
        )

    if request.method == "POST":
        form = SoloForm(request.POST)
        if not core_passed or not moodle_completed:
            form.add_error(None, "User has not completed all requirements.")

            return render(
                request,
                "overview/solo.html",
                {
                    "form": form,
                    "course": course,
                    "vatsim_id": vatsim_id,
                    "moodle": moodle_completed,
                    "core": core_passed,
                },
            )

        if form.is_valid():
            dt = form.cleaned_data["expiry"]
            dt_with_time = datetime(dt.year, dt.month, dt.day, 23, 59, 00)
            formatted_str = dt_with_time.strftime("%Y-%m-%dT%H:%M:%S.%f")[:-3] + "Z"
            data = {
                "user_cid": vatsim_id,
                "position": course.solo_station,
                "expire_at": formatted_str,
                "instructor_cid": request.user.username,
            }
            res = requests.post(
                "https://core.vateud.net/api/facility/endorsements/solo",
                headers=eud_header,
                json=data,
            )
            if res.status_code == 200:
                return redirect("overview:overview")
            else:
                form.add_error(None, res.json()["message"])
    else:
        form = SoloForm()

    return render(
        request,
        "overview/solo.html",
        {
            "form": form,
            "course": course,
            "vatsim_id": vatsim_id,
            "moodle": moodle_completed,
            "core": core_passed,
        },
    )


@login_required
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

    # Add familiarisations if centre course
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


@login_required
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


@login_required
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


@login_required
def assign_core_test_view(request, vatsim_id: int, course_id: int):
    course = get_object_or_404(Course, id=course_id)
    if request.user not in course.mentors.all():
        return redirect("overview:overview")
    if get_core_theory_passed(vatsim_id, course.position) != CoreState.NOT_ASSIGNED:
        return redirect("overview:overview")
    assign_core_test(request.user.username, vatsim_id, course.position)
    return redirect("overview:overview")
