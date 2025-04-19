import requests
from cachetools import cached, TTLCache
from django.conf import settings
from dotenv import load_dotenv
from training.eud_header import eud_header

load_dotenv()


@cached(cache=TTLCache(maxsize=1024, ttl=60 * 10))
def get_tier1_endorsements():
    if settings.USE_CORE_MOCK:
        return [
            {
                "id": 1,
                "user_cid": 1439797,
                "instructor_cid": 1439797,
                "position": "EDDB_TWR",
                "facility": 9,
                "created_at": "2025-04-19T12:02:38.000000Z",
                "updated_at": "2025-04-19T12:02:38.000000Z",
            },
            {
                "id": 2,
                "user_cid": 1000000,
                "instructor_cid": 1439797,
                "position": "EDDF_TWR",
                "facility": 9,
                "created_at": "2025-04-19T12:02:38.000000Z",
                "updated_at": "2025-04-19T12:02:38.000000Z",
            },
        ]
    endorsements = requests.get(
        "https://core.vateud.net/api/facility/endorsements/tier-1", headers=eud_header
    ).json()["data"]
    return endorsements


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
    t2 = get_tier2_endorsements()
    t1 = [
        endorsement
        for endorsement in t1
        if endorsement["controller"]["user_cid"] == vatsim_id
    ]
    t2 = [
        endorsement
        for endorsement in t2
        if endorsement["controller"]["user_cid"] == vatsim_id
    ]
    for endorsement in t1:
        requests.delete(
            f"https://core.vateud.net/api/facility/endorsements/tier-1/{endorsement['id']}",
            headers=eud_header,
        )
    for endorsement in t2:
        requests.delete(
            f"https://core.vateud.net/api/facility/endorsements/tier-2/{endorsement['id']}",
            headers=eud_header,
        )
