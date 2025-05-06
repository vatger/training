import requests
from cachetools import cached, TTLCache
from django.core.management.base import BaseCommand
from familiarisations.models import Familiarisation
from lists.models import WaitingListEntry
from lists.views import get_user_endorsements
from training.eud_header import eud_header
from training.helpers import log_admin_action
from django.contrib.auth.models import User
from dotenv import load_dotenv
import os
from django.contrib.admin.models import DELETION

load_dotenv()


@cached(cache=TTLCache(maxsize=100, ttl=60))
def get_roster():
    return requests.get(
        "https://core.vateud.net/api/facility/roster", headers=eud_header
    ).json()["data"]["controllers"]


def course_valid_for_user(course, user):
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

    if int(user.username) not in get_roster() and user.userdetail.subdivision == "GER":
        # Only check for GER users as guests might not be on roster yet
        print("User not on roster")
        return False, "You are not on the roster."
    return True, ""


class Command(BaseCommand):
    help = "Daily command to clean waiting list"

    def handle(self, *args, **options):
        # Get all entries
        entries = WaitingListEntry.objects.all()
        for entry in entries:
            valid, reason = course_valid_for_user(entry.course, entry.user)
            if not valid:
                print(f"Deleting {entry} as it is invalid")
                entry.delete()
                log_admin_action(
                    User.objects.get(username=os.getenv("ATD_LEAD_CID")),
                    entry,
                    DELETION,
                    f"Removed {entry}, date added: {entry.date_added}.",
                )

                data = {
                    "title": "Waiting List Removal",
                    "message": f"You have been removed from the waiting list for the {entry.course.name} course. {reason} If you have any questions, please contact the VATGER ATD.",
                    "source_name": "VATGER ATD",
                }
                header = {"Authorization": f"Token {os.getenv("VATGER_API_KEY")}"}
                r = requests.post(
                    f"http://vatsim-germany.org/api/user/{entry.user.username}/send_notification",
                    data=data,
                    headers=header,
                )
