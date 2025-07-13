import requests
from cachetools import cached, TTLCache
from django.conf import settings
from dotenv import load_dotenv
from training.eud_header import eud_header

load_dotenv()


def sort_endorsements(endorsement):
    position = endorsement["position"]

    if position.endswith("_CTR"):
        ctr_code = position[:-4]
        return ("0_CTR", ctr_code)

    parts = position.split("_")
    if len(parts) >= 2:
        airport = parts[0]
        endorsement_type = "_".join(parts[1:])

        type_priority = {"APP": "1", "TWR": "2", "GNDDEL": "3"}
        priority = type_priority.get(endorsement_type, "9")

        return (f"1_{airport}", priority)

    return (f"9_{position}", "")


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
        ]
    endorsements = requests.get(
        "https://core.vateud.net/api/facility/endorsements/tier-1", headers=eud_header
    ).json()["data"]

    res_t1 = sorted(endorsements, key=sort_endorsements)
    return res_t1


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
    t1 = [endorsement for endorsement in t1 if endorsement["user_cid"] == vatsim_id]
    t2 = [endorsement for endorsement in t2 if endorsement["user_cid"] == vatsim_id]
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
