from datetime import datetime

import requests
from django.shortcuts import render, redirect, get_object_or_404
from lists.models import Course
from training.eud_header import eud_header
from training.permissions import mentor_required

from .forms import SoloForm
from .helpers import get_core_theory_passed, get_course_completion


@mentor_required
def add_solo(request, vatsim_id, course_id):
    course = get_object_or_404(Course, id=course_id)
    if course.type != "RTG":
        return redirect("overview:overview")

    if course not in request.user.mentored_courses.all():
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


@mentor_required
def delete_solo(request, solo_id: int):
    requests.delete(
        f"https://core.vateud.net/api/facility/endorsements/solo/{solo_id}",
        headers=eud_header,
    )
    return redirect("overview:overview")
