from datetime import datetime, timezone

import requests
from django.contrib.auth.decorators import login_required
from django.contrib.auth.models import User
from django.shortcuts import redirect, get_object_or_404
from django.shortcuts import render
from dotenv import load_dotenv
from lists.models import Course
from lists.views import enrol_into_required_moodles
from logs.models import Log
from overview.models import TraineeClaim
from training.eud_header import eud_header

from .forms import AddUserForm, SoloForm
from .helpers import get_course_completion, get_core_theory_passed

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
                and solo["user_cid"] == int(trainee.username)
            ]
            solo_info = (
                f"{solo[0]["remaining_days"]}/{solo[0]["delta"]}"
                if solo
                else "Add Solo"
            )
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
        if request.user in course.mentors.all():
            course.active_trainees.remove(trainee_id)
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
            print(res)
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
    return redirect("overview:manage_mentors", course_id=course_id)
