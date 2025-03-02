from django.core.management.base import BaseCommand
from endorsements.models import EndorsementActivity, EndorsementGroup
from endorsements.helpers import get_tier1_endorsements
from django.utils import timezone
import requests


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
            if connection["callsign"][:6] == endorsement["position"][:6]:
                activity_min += float(connection["minutes_on_callsign"])
    else:
        station = endorsement["position"].split("_")[-1]
        apt = endorsement["position"].split("_")[0]
        stations_to_consider = ctr_topdown[apt]
        for connection in connections:
            if connection["callsign"][:6] in stations_to_consider or suffix_condition(
                apt, station, connection["callsign"]
            ):
                activity_min += float(connection["minutes_on_callsign"])
    return activity_min


def get_hours(endorsement: dict) -> float:
    vatsim_id = endorsement["user_cid"]
    start = timezone.now() - timezone.timedelta(days=180)
    start = start.strftime("%Y-%m-%d")
    api_url = (
        lambda id, start: f"https://api.vatsim.net/api/ratings/{id}/atcsessions/?start={start}"
    )
    try:
        response = requests.get(api_url(vatsim_id, start)).json()["results"]
    except:
        return -1
    activity = calculate_activity(endorsement, response)
    return activity


class Command(BaseCommand):
    help = "Update hours controlled using VATSIM API"

    def handle(self, *args, **kwargs):
        tier1_endorsements = get_tier1_endorsements()
        # todo remove this for production
        for t1 in tier1_endorsements[10:]:
            try:
                EndorsementActivity.objects.get(id=t1["id"])
            except EndorsementActivity.DoesNotExist:
                group = EndorsementGroup.objects.get(name=t1["position"])
                EndorsementActivity.objects.create(
                    id=t1["id"],
                    activity=0.0,
                    updated=timezone.now(),
                    group=group,
                )

        endorsement = EndorsementActivity.objects.order_by("updated").first()

        if endorsement:
            # Get endorsement from list of tier1_endorsements by id, and delete EndorsementActivity if no longer exists
            try:
                tier1_entry = next(
                    item for item in tier1_endorsements if item["id"] == endorsement.id
                )
                print(tier1_entry)
            except StopIteration:
                endorsement.delete()
                self.stdout.write("EndorsementActivity entry deleted.")
                return

            hours = get_hours(tier1_entry)
            if hours == -1:
                self.stdout.write("Error fetching hours from VATSIM API.")
                return
            endorsement.activity = hours
            endorsement.updated = timezone.now()
            endorsement.save()
            self.stdout.write(f"Updated hours.")
        else:
            self.stdout.write("No waiting list entries found.")
