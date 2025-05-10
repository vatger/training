import requests
from cachetools import TTLCache, cached
from training.eud_header import eud_header
from familiarisations.models import Familiarisation
from endorsements.helpers import get_tier1_endorsements


@cached(cache=TTLCache(maxsize=100, ttl=60))
def get_roster():
    return requests.get(
        "https://core.vateud.net/api/facility/roster", headers=eud_header
    ).json()["data"]["controllers"]


@cached(cache=TTLCache(maxsize=1024, ttl=60 * 60))
def get_user_endorsements(user_id: int) -> set:
    return set(
        [
            end["position"]
            for end in get_tier1_endorsements()
            if end["user_cid"] == user_id
        ]
    )


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
    ):
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

    return True, ""
