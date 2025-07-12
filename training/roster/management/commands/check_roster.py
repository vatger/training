import os
from dotenv import load_dotenv
import requests
from datetime import datetime

from django.core.management.base import BaseCommand
from django.utils import timezone

from lists.models import WaitingListEntry
from roster.models import RosterEntry
from endorsements.helpers import remove_roster_and_endorsements
from training.eud_header import eud_header

load_dotenv()


def get_last_session(vatsim_id: int):
    # Get sessions from past year
    date = timezone.now() - timezone.timedelta(days=365)
    connections = requests.get(
        f"https://api.vatsim.net/api/ratings/{vatsim_id}/atcsessions/?start={date.year}-{date.month}-{date.day}",
    ).json()["results"]
    for connection in connections:
        if connection["callsign"][:2] in ["ED", "ET"]:
            time = datetime.fromisoformat(connection["end"])
            return timezone.make_aware(time)
    print(f"No connections for this user {vatsim_id}")
    return timezone.make_aware(datetime(1970, 1, 1, 0, 0, 0))


def s1_check(vatsim_id: int):
    api_res = requests.get(f"https://api.vatsim.net/api/ratings/{vatsim_id}/").json()
    rating = api_res["rating"]
    if rating == 2:
        rating_change = datetime.fromisoformat(api_res["lastratingchange"])
        rating_change = timezone.make_aware(rating_change)
        return (
            timezone.now() - rating_change < timezone.timedelta(days=11 * 4.33 * 7),
            rating_change,
        )
    else:
        return False, None


def inform_roster_removal(vatsim_id: int):
    data = {
        "title": "Removal from VATSIM Germany Roster",
        "message": f"""You have not controlled in the past 11 months. 
        If you want to stay on the VATSIM Germany roster, 
        please log in to the VATSIM network and control at least once in the next 35 days. 
        If you do not, your account will be removed from the roster. If you believe this is a mistake, please contact the ATD.""",
        "source_name": "VATGER ATD",
        "via": "board.ping",
    }
    header = {"Authorization": f"Token {os.getenv("VATGER_API_KEY")}"}
    r = requests.post(
        f"http://vatsim-germany.org/api/user/{vatsim_id}/send_notification",
        data=data,
        headers=header,
    )


class Command(BaseCommand):
    help = "Command to check roster"

    def handle(self, *args, **options):
        roster = requests.get(
            "https://core.vateud.net/api/facility/roster", headers=eud_header
        ).json()["data"]["controllers"]
        for vatsim_id in roster:
            entry, _ = RosterEntry.objects.get_or_create(user_id=vatsim_id)
            if entry.last_session.tzinfo is None:
                entry.last_session = timezone.make_aware(entry.last_session)
                entry.save()
            if timezone.now() - entry.last_session < timezone.timedelta(
                days=11 * 4.33 * 7
            ):
                continue
            try:
                entry.last_session = get_last_session(vatsim_id)
                if entry.last_session.tzinfo is None:
                    entry.last_session = timezone.make_aware(entry.last_session)
                entry.save()
            except:
                print(f"Error getting last session for {vatsim_id}")
                continue
            if (
                entry.last_session < timezone.now() - timezone.timedelta(days=366)
                and entry.removal_date is not None
            ):
                if entry.removal_date < timezone.now():
                    entry.delete()
                    remove_roster_and_endorsements(entry.user_id)
                    # Remove all waiting list entries for the given user
                    waiting_list_entries = WaitingListEntry.objects.filter(
                        user__username=entry.user_id
                    )
                    waiting_list_entries.delete()
                    continue
                else:
                    continue
            if entry.last_session < timezone.now() - timezone.timedelta(
                days=11 * 4.33 * 7
            ):
                try:
                    check, change_date = s1_check(vatsim_id)
                except:
                    print(f"Error getting rating for {vatsim_id}")
                    continue
                if check:
                    entry.last_session = change_date
                    entry.removal_date = None
                    entry.save()
                    continue
                if entry.removal_date is None:
                    inform_roster_removal(vatsim_id)
                    entry.removal_date = timezone.now() + timezone.timedelta(days=35)
                    entry.save()
            else:
                entry.removal_date = None
                entry.save()
