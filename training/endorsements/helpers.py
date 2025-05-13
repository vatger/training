from datetime import timedelta

import requests
from cachetools import cached, TTLCache
from django.conf import settings
from django.utils import timezone
from dotenv import load_dotenv
from training.eud_header import eud_header

load_dotenv()


@cached(cache=TTLCache(maxsize=1024, ttl=60 * 10))
def get_tier1_endorsements():
    if settings.USE_CORE_MOCK:
        return [
            {
                "id": 1,
                "user_cid": 1601613,
                "instructor_cid": 1439797,
                "position": "EDDL_TWR",
                "facility": 9,
                "created_at": "2025-04-19T12:02:38.000000Z",
                "updated_at": "2025-04-19T12:02:38.000000Z",
            },
            {
                "id": 2,
                "user_cid": 1601613,
                "instructor_cid": 1439797,
                "position": "EDDL_APP",
                "facility": 9,
                "created_at": "2025-04-19T12:02:38.000000Z",
                "updated_at": "2025-04-19T12:02:38.000000Z",
            },
        ]
    endorsements = requests.get(
        "https://core.vateud.net/api/facility/endorsements/tier-1", headers=eud_header
    ).json()["data"]
    return endorsements


@cached(cache=TTLCache(maxsize=1024, ttl=60 * 10))
def get_tier2_endorsements():
    if settings.USE_CORE_MOCK:
        return [
            {
                "id": 25,
                "user_cid": 1439797,
                "instructor_cid": 1439797,
                "position": "EDXX_AFIS",
                "facility": 9,
                "created_at": "2024-02-29T22:39:33.000000Z",
                "updated_at": "2024-02-29T22:39:33.000000Z",
            },
            {
                "id": 26,
                "user_cid": 1000000,
                "instructor_cid": 1439797,
                "position": "EDXX_AFIS",
                "facility": 9,
                "created_at": "2024-02-29T22:39:33.000000Z",
                "updated_at": "2024-02-29T22:39:33.000000Z",
            },
        ]

    endorsements = requests.get(
        "https://core.vateud.net/api/facility/endorsements/tier-2", headers=eud_header
    ).json()["data"]
    return endorsements


def remove_roster_and_endorsements(vatsim_id: int):
    if settings.USE_CORE_MOCK:
        return
    # Roster
    requests.delete(
        f"https://core.vateud.net/api/facility/roster/{vatsim_id}",
        headers=eud_header,
    )
    t1 = get_tier1_endorsements()
    t1 = [endorsement for endorsement in t1 if endorsement["user_cid"] == vatsim_id]
    for endorsement in t1:
        requests.delete(
            f"https://core.vateud.net/api/facility/endorsements/tier-1/{endorsement['id']}",
            headers=eud_header,
        )

    # Get and remove tier 2 endorsements
    t2 = get_tier2_endorsements()
    t2 = [endorsement for endorsement in t2 if endorsement["user_cid"] == vatsim_id]
    for endorsement in t2:
        requests.delete(
            f"https://core.vateud.net/api/facility/endorsements/tier-2/{endorsement['id']}",
            headers=eud_header,
        )


def valid_removal(endorsement):
    from endorsements.models import EndorsementActivity

    if not isinstance(endorsement, EndorsementActivity):
        return False

    no_min_hours = endorsement.is_low_activity
    six_months_ago = timezone.now() - timedelta(days=180)
    not_recent = endorsement.created < six_months_ago

    return no_min_hours and not_recent


def get_user_endorsements_by_group(user_id: int):
    from endorsements.models import EndorsementGroup, EndorsementActivity

    # Get all endorsement groups
    groups = EndorsementGroup.objects.all().order_by("name")

    # Get tier 1 endorsements for the user
    t1_endorsements = [
        end for end in get_tier1_endorsements() if end["user_cid"] == user_id
    ]

    # Get activities for those endorsements
    activities = {
        act.id: act
        for act in EndorsementActivity.objects.filter(
            id__in=[end["id"] for end in t1_endorsements]
        )
    }

    # Group by endorsement group
    endorsements_by_group = {}
    for group in groups:
        endorsements_by_group[group.name] = []

    # Populate groups with endorsement data
    for end in t1_endorsements:
        if end["position"] in endorsements_by_group:
            if end["id"] in activities:
                activity = activities[end["id"]]
                endorsements_by_group[end["position"]].append(
                    {
                        "id": end["position"],
                        "activity": activity.activity_hours,
                        "removal_date": activity.removal_date,
                        "updated": activity.updated,
                    }
                )

    return endorsements_by_group
