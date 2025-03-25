import os
from datetime import datetime, timezone
from enum import Enum

import requests
from cachetools import cached, TTLCache
from dotenv import load_dotenv
from training.eud_header import eud_header

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
    header = {"Authorization": f"Token {os.getenv("VATGER_API_KEY")}"}
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
