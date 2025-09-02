import os
from datetime import datetime, timezone

import requests
from cachetools import TTLCache, cached
from dotenv import load_dotenv
from training.eud_header import eud_header

from endorsements.helpers import get_tier1_endorsements
from familiarisations.models import Familiarisation

load_dotenv()


@cached(cache=TTLCache(maxsize=100, ttl=60))
def get_roster():
    try:
        response = requests.get(
            "https://core.vateud.net/api/facility/roster",
            headers=eud_header,
            timeout=10,
        )
        response.raise_for_status()  # Raises an HTTPError for bad responses

        data = response.json()

        # Handle different possible response structures
        if "data" in data and "controllers" in data["data"]:
            return data["data"]["controllers"]
        elif "controllers" in data:
            return data["controllers"]
        elif isinstance(data, list):
            # If the response is directly a list of controllers
            return data
        else:
            print(f"Unexpected roster API response structure: {data}")
            return []

    except requests.exceptions.RequestException as e:
        print(f"Network error fetching roster: {e}")
        return []
    except ValueError as e:  # JSON decode error
        print(f"Invalid JSON response from roster API: {e}")
        return []
    except KeyError as e:
        print(f"Missing key in roster API response: {e}")
        return []
    except Exception as e:
        print(f"Unexpected error fetching roster: {e}")
        return []


@cached(cache=TTLCache(maxsize=1024, ttl=60 * 60))
def get_user_endorsements(user_id: int) -> set:
    try:
        endorsements = get_tier1_endorsements()
        return set(
            [end["position"] for end in endorsements if end["user_cid"] == user_id]
        )
    except Exception as e:
        print(f"Error fetching user endorsements for {user_id}: {e}")
        return set()


def course_valid_for_user(course, user) -> [bool, str]:
    """
    Check whether a user can enter the waiting list for a given course.
    Checks for rating, endorsements and familiarisation.
    :param course:
    :param user:
    :return:
    """
    if (
        not (course.min_rating <= user.userdetail.rating <= course.max_rating)
        and course.type != "GST"
    ):  # check disabled if guest as rating might change outside VATGER
        print("User rating does not match course rating")
        return False, "You do not have the required rating for this course."

    if user.active_courses.all().filter(type="RTG").exists() and course.type == "RTG":
        print("User has active RTG course")
        return False, "You already have an active RTG course."

    if user.userdetail.subdivision == "GER" and course.type == "GST":
        print("GST course for GER user")
        return (
            False,
            "You are not allowed to enter the waiting list for a visitor course.",
        )
    if user.userdetail.subdivision != "GER" and course.type == "RTG":
        print("RTG course for non-GER user")
        return (
            False,
            "You are not allowed to enter the waiting list for a rating course.",
        )

    if (
        course.familiarisation_sector
        and Familiarisation.objects.filter(
            user=user, sector=course.familiarisation_sector
        ).exists()
    ):
        print("Familiarisation exists")
        return False, "You already have a familiarisation for this course."

    endorsement_groups = set(
        course.endorsement_groups.all().values_list("name", flat=True)
    )
    if (
        len(endorsement_groups & get_user_endorsements(user.username))
        == len(endorsement_groups)
        and len(endorsement_groups) > 0
    ) and course.type == "EDMT":
        print("Endorsements exist")
        return False, "You already have the required endorsements for this course."

    if (
        int(user.username) not in get_roster()
        and user.userdetail.subdivision == "GER"
        and course.type != "RST"
    ):
        # Only check for GER users as guests might not be on roster yet
        print("User not on roster")
        return False, "You are not on the roster."

    if int(user.username) in get_roster() and course.type == "RST":
        print("User on roster in RST course")
        return (
            False,
            "You are already on the roster.",
        )

    if (
        user.userdetail.rating == 3
        and course.type == "RTG"
        and course.position == "APP"
    ):
        if user.userdetail.last_rating_change is not None and (
            datetime.now(timezone.utc) - user.userdetail.last_rating_change
        ).days < int(os.getenv("S3_RATING_CHANGE_DAYS", 90)):
            return (
                False,
                "Your last rating change was less than 3 months ago. You cannot join an S3 course yet.",
            )
    return True, ""


@cached(cache=TTLCache(maxsize=float("inf"), ttl=60 * 10))
def send_moodle_find_user(user_id: int) -> bool:
    header = {"Authorization": f"Token {os.getenv('VATGER_API_KEY')}"}
    r = requests.get(
        f"http://vatsim-germany.org/api/moodle/user/{user_id}",
        headers=header,
    ).json()
    try:
        id = r["id"]
        return True
    except:
        return False
