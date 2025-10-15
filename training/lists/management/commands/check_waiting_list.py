from django.core.management.base import BaseCommand
from lists.models import WaitingListEntry
from django.utils import timezone
import requests


api_url = (
    lambda id, start: f"http://stats.vatsim-germany.org/api/atc/{id}/sessions/?start={start}"
)


def equal_str(a: str, b: str) -> bool:
    return a.split("_")[0] == b.split("_")[0] and a.split("_")[-1] == b.split("_")[-1]


def get_hours(id: int, airport: str, position: str, fir: str):
    # Calculate date two months ago
    start = timezone.now() - timezone.timedelta(days=60)
    # format to YYYY-MM-DD
    start = start.strftime("%Y-%m-%d")
    try:
        # Get ATC sessions
        response = requests.get(api_url(id, start)).json()
    except:
        return -1
    match position:
        case "GND" | "TWR":
            url = f"https://raw.githubusercontent.com/VATGER-Nav/datahub/refs/heads/production/api/{fir.lower()}/twr.json"
            hub = requests.get(url).json()
            # Get S1 Tower Stations
            filtered = [
                station
                for station in hub
                if "s1_twr" in station.keys() and station["logon"].split("_")[1] != "I"
            ]
            filtered = [station for station in filtered if station["s1_twr"] == True]
            stations = [station["logon"] for station in filtered]
            # Calculate hours
            hours = sum(
                float(session["minutes_on_callsign"]) / 60
                for session in response
                if any(equal_str(session["callsign"], station) for station in stations)
            )
        case "APP":
            hours = sum(
                float(session["minutes_on_callsign"]) / 60
                for session in response
                if equal_str(session["callsign"], f"{airport.upper()}_TWR")
            )
        case "CTR":
            # TODO: Implement CTR
            hours = 10
        case _:
            hours = -1
    return hours


class Command(BaseCommand):
    help = "Update hours controlled using VATSIM API. Will be run every minute"

    def handle(self, *args, **kwargs):
        # Get waiting list entries where their course type is RTG and order by hours_updated
        waiting_list_entry = (
            WaitingListEntry.objects.filter(course__type="RTG")
            .order_by("hours_updated")
            .first()
        )
        if waiting_list_entry:
            hours = get_hours(
                waiting_list_entry.user.username,
                waiting_list_entry.course.airport_icao,
                waiting_list_entry.course.position,
                waiting_list_entry.course.mentor_group.name[:4],
            )
            if hours == -1:
                self.stdout.write("Error fetching hours from VATSIM API.")
                return
            waiting_list_entry.activity = hours
            waiting_list_entry.hours_updated = timezone.now()
            waiting_list_entry.save()
            self.stdout.write(
                f"Updated hours for {waiting_list_entry.user.first_name} {waiting_list_entry.user.last_name}: {hours}"
            )
        else:
            self.stdout.write("No waiting list entries found.")
