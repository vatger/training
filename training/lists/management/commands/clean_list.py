from django.core.management.base import BaseCommand
from familiarisation.models import Familiarisation
from lists.models import WaitingListEntry
from lists.views import get_user_endorsements
from training.eud_header import eud_header
from cachetools import cached, TTLCache
import requests


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
    if not (course.min_rating <= user.rating <= course.max_rating):
        print("User rating does not match course rating")
        return False

    if user.active_courses.all().filter(type="RTG").exists():
        print("User has active RTG course")
        return False

    if user.userdetail.subdivision == "GER" and course.type == "GST":
        print("GST course for GER user")
        return False
    if user.userdetail.subdivision != "GER" and course.type == "RTG":
        print("RTG course for non-GER user")
        return False

    if (
        course.familiarisation_sector
        and Familiarisation.objects.filter(
            user=user, sector=course.familiarisation_sector
        ).exists()
    ):
        print("Familiarisation exists")
        return False

    endorsement_groups = set(
        course.endorsement_groups.all().values_list("name", flat=True)
    )
    if (
        len(endorsement_groups & get_user_endorsements(user.username))
        == len(endorsement_groups)
        and len(endorsement_groups) > 0
    ):
        print("Endorsements exist")
        return False

    if int(user.username) not in get_roster():
        print("User not on roster")
        return False
    return True


class Command(BaseCommand):
    help = "Daily command to clean waiting list"

    def handle(self, *args, **options):
        # Get all entries
        entries = WaitingListEntry.objects.all()
        for entry in entries:
            if not course_valid_for_user(entry.course, entry.user):
                print(f"Deleting {entry} as it is invalid")
                # entry.delete()
