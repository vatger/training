import os
from datetime import datetime, timezone

import requests
from django.core.management.base import BaseCommand
from django.utils import timezone as dj_timezone
from dotenv import load_dotenv
from endorsements.helpers import get_tier1_endorsements
from endorsements.models import EndorsementActivity, EndorsementGroup

from tqdm import tqdm

load_dotenv()

viable_suffixes = {
    # endorsement: [suffixes]
    "APP": ["APP", "DEP"],
    "TWR": ["APP", "DEP", "TWR"],
    "GNDDEL": ["APP", "DEP", "TWR", "GND", "DEL"],
}

ctr_topdown = {
    # APT: [Stations]
    "EDDB": ["EDWW_F", "EDWW_B", "EDWW_K", "EDWW_M", "EDWW_C"],
    "EDDH": ["EDWW_H", "EDWW_A", "EDWW_W", "EDWW_C"],
    "EDDF": [
        "EDGG_G",
        "EDGG_R",
        "EDGG_D",
        "EDGG_B",
        "EDGG_K",
    ],
    "EDDK": ["EDGG_P"],
    "EDDL": ["EDGG_P"],
    "EDDM": ["EDMM_N", "EDMM_Z", "EDMM_R"],
}


def suffix_condition(endorsement_apt: str, endorsement_station: str, callsign: str):
    cs_apt = callsign.split("_")[0]
    cs_station = callsign.split("_")[-1]
    return (
        cs_apt == endorsement_apt and cs_station in viable_suffixes[endorsement_station]
    )


def calculate_activity(endorsement: dict, connections: list[dict]) -> float:
    activity_min = 0
    if endorsement["position"].split("_")[-1] == "CTR":
        for connection in connections:
            if connection["callsign"][:6] == endorsement["position"][:6] or (
                endorsement["position"] == "EDWW_W_CTR"
                and connection["callsign"] == "EDWW_CTR"
            ):
                activity_min += float(connection["minutes_online"])
    else:
        station = endorsement["position"].split("_")[-1]
        apt = endorsement["position"].split("_")[0]
        stations_to_consider = ctr_topdown[apt]
        for connection in connections:
            if connection["callsign"][:6] in stations_to_consider or suffix_condition(
                apt, station, connection["callsign"]
            ):
                activity_min += float(connection["minutes_online"])
    return activity_min


def get_hours(endorsement: dict) -> float:
    vatsim_id = endorsement["user_cid"]
    start = dj_timezone.now() - dj_timezone.timedelta(days=180)
    start = start.strftime("%Y-%m-%d")
    api_url = (
        lambda id, start: f"http://stats.vatsim-germany.org/api/atc/{id}/sessions/?start_date={start}"
    )
    try:
        response = requests.get(api_url(vatsim_id, start)).json()
    except:
        return -1
    activity = calculate_activity(endorsement, response)
    return activity


class Command(BaseCommand):
    help = """Update hours controlled using VATSIM API.
    Will send removal inforation if controller is marked for deletion.
    Is run every minute to keep Endorsement Activities up to date."""

    def handle(self, *args, **kwargs):
        tier1_endorsements = get_tier1_endorsements()
        for t1 in tier1_endorsements:
            try:
                EndorsementActivity.objects.get(id=t1["id"])
            except EndorsementActivity.DoesNotExist:
                try:
                    group = EndorsementGroup.objects.get(name=t1["position"])
                except EndorsementGroup.DoesNotExist:
                    continue
                created_at = datetime.strptime(
                    t1["created_at"], "%Y-%m-%dT%H:%M:%S.%fZ"
                ).replace(tzinfo=timezone.utc)

                EndorsementActivity.objects.create(
                    id=t1["id"],
                    activity=0.0,
                    updated=dj_timezone.now(),
                    group=group,
                    created=created_at,
                )

        endorsement = EndorsementActivity.objects.order_by("updated").first()
        endorsements = EndorsementActivity.objects.order_by("updated")

        for endorsement in tqdm(endorsements):
            # Get endorsement from list of tier1_endorsements by id, and delete EndorsementActivity if no longer exists
            try:
                tier1_entry = next(
                    item for item in tier1_endorsements if item["id"] == endorsement.id
                )
            except StopIteration:
                endorsement.delete()
                self.stdout.write("EndorsementActivity entry deleted.")
                return

            hours = get_hours(tier1_entry)
            if hours == -1:
                self.stdout.write("Error fetching hours from VATSIM API.")
                return
            if hours >= float(os.getenv("T1_MIN_MINUTES")):
                # Set removal_date field to blank
                endorsement.removal_date = None
            else:
                if endorsement.removal_date and not endorsement.removal_notified:
                    # Send notification

                    data = {
                        "title": "Endorsement Removal",
                        "message": f"""Your endorsement for {endorsement.group.name} will be removed on {endorsement.removal_date.strftime("%d.%m.%Y")}.
                        If you wish to keep it, please ensure you meet the minimum activity requirements by then.""",
                        "source_name": "VATGER ATD",
                        "via": "board.ping",
                    }
                    header = {"Authorization": f"Token {os.getenv("VATGER_API_KEY")}"}
                    r = requests.post(
                        f"http://vatsim-germany.org/api/user/{tier1_entry["user_cid"]}/send_notification",
                        data=data,
                        headers=header,
                    )
                    if r.status_code == 200:
                        endorsement.removal_notified = True
                    self.stdout.write("Sent notification.")

            endorsement.activity = hours
            endorsement.updated = dj_timezone.now()
            endorsement.save()
            self.stdout.write(f"Updated hours.")
        else:
            self.stdout.write("No waiting list entries found.")
