import requests
from cachetools import TTLCache, cached
from training.eud_header import eud_header


@cached(cache=TTLCache(maxsize=100, ttl=60))
def get_roster():
    return requests.get(
        "https://core.vateud.net/api/facility/roster", headers=eud_header
    ).json()["data"]["controllers"]
