import requests
from cachetools import cached, TTLCache
from dotenv import load_dotenv
from training.eud_header import eud_header

load_dotenv()


@cached(cache=TTLCache(maxsize=1024, ttl=60 * 10))
def get_tier1_endorsements():
    endorsements = requests.get(
        "https://core.vateud.net/api/facility/endorsements/tier-1", headers=eud_header
    ).json()["data"]
    return endorsements


def get_tier2_endorsements():
    endorsements = requests.get(
        "https://core.vateud.net/api/facility/endorsements/tier-2", headers=eud_header
    ).json()["data"]
    return endorsements


def remove_roster_and_endorsements(vatsim_id: int):
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
