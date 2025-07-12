import os
from datetime import datetime, timezone
from enum import Enum

import requests
from cachetools import cached, TTLCache
from django.contrib.admin.models import CHANGE
from dotenv import load_dotenv
from training.eud_header import eud_header
from training.helpers import log_admin_action

from familiarisations.models import Familiarisation, FamiliarisationSector

load_dotenv()


class CoreState(Enum):
    PASSED = "Passed"
    NOT_ASSIGNED = "Not Assigned"
    ASSIGNED = "Assigned"


core_theory_ids = {
    "GND": 6,
    "TWR": 9,
    "APP": 10,
    "CTR": 11,
}


@cached(cache=TTLCache(maxsize=1024, ttl=60 * 10))
def get_course_completion(user_id: int, course_id: int) -> bool:
    header = {"Authorization": f"Token {os.getenv('VATGER_API_KEY')}"}
    link = f"http://vatsim-germany.org/api/moodle/course/{course_id}/user/{user_id}/completion"
    r = requests.get(link, headers=header)
    if r.status_code == 200:
        r = r.json()
        return r["completed"]
    else:
        return False


def get_core_theory_passed(user_id: int, position: str) -> CoreState:
    try:
        res = requests.get(
            f"https://core.vateud.net/api/facility/user/{user_id}/exams",
            headers=eud_header,
        ).json()["data"]
        filtered = [
            test
            for test in res["results"]
            if test["exam_id"] == core_theory_ids[position]
            and test["passed"]
            and datetime.strptime(test["expiry"], "%Y-%m-%dT%H:%M:%S.%fZ").replace(
                tzinfo=timezone.utc
            )
            > datetime.now(timezone.utc)
        ]
        if bool(filtered):
            return CoreState.PASSED
        assignments = [
            test
            for test in res["assignments"]
            if test["exam_id"] == core_theory_ids[position]
            and datetime.strptime(test["expires"], "%Y-%m-%dT%H:%M:%S.%fZ").replace(
                tzinfo=timezone.utc
            )
            > datetime.now(timezone.utc)
        ]
        if bool(assignments):
            return CoreState.ASSIGNED
        return CoreState.NOT_ASSIGNED
    except:
        return CoreState.NOT_ASSIGNED


def assign_core_test(instructor_id: int, vatsim_id: int, position: str):
    data = {
        "user_cid": vatsim_id,
        "exam_id": core_theory_ids[position],
        "instructor_cid": instructor_id,
    }
    requests.post(
        "https://core.vateud.net/api/facility/training/exams/assign",
        headers=eud_header,
        json=data,
    )


def inform_user_course_start(vatsim_id: int, course_name: str):
    data = {
        "title": "Start of Training",
        "message": f"""You have been enrolled in the {course_name} course. Check the training centre for moodle 
        courses to start your training.""",
        "source_name": "VATGER ATD",
        "link_text": "Training Centre",
        "link_url": "https://training.vatsim-germany.org/",
        "via": "board.ping",
    }
    header = {"Authorization": f"Token {os.getenv('VATGER_API_KEY')}"}
    r = requests.post(
        f"http://vatsim-germany.org/api/user/{vatsim_id}/send_notification",
        data=data,
        headers=header,
    )


def complete_trainee_course(request_user, trainee, course):
    """Complete a trainee's course by removing them from active trainees
    and adding appropriate endorsements and familiarisations"""
    course.active_trainees.remove(trainee.id)
    log_admin_action(
        request_user,
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
                "instructor_cid": request_user.username,
            },
        )

    if course.type == "RTG" and course.position == "CTR":
        fir = course.mentor_group.name[:4]  # Extract FIR code from mentor group name
        sectors = FamiliarisationSector.objects.filter(fir=fir)
        for sector in sectors:
            if not Familiarisation.objects.filter(user=trainee, sector=sector).exists():
                Familiarisation.objects.create(user=trainee, sector=sector)
                log_admin_action(
                    request_user,
                    course,
                    CHANGE,
                    f"Added familiarisation {sector} ({sector.name}) to trainee {trainee} ({trainee.username})",
                )
    elif course.type == "FAM":
        # Create familiarisation if does not exist
        if course.familiarisation_sector is None:
            return
        _, _ = Familiarisation.objects.get_or_create(
            user=trainee, sector=course.familiarisation_sector
        )
