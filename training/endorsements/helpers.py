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
