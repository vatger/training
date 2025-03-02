import os
import requests
from cachetools import TTLCache, cached
from dotenv import load_dotenv

load_dotenv()


# @cached(cache=TTLCache(maxsize=1024, ttl=60 * 10))
def get_tier1_endorsements():
    eud_header = {
        "X-API-KEY": os.getenv("CORE_API"),
        "Accept": "application/json",
        "User-Agent": "VATGER",
    }
    endorsements = requests.get(
        "https://core.vateud.net/api/facility/endorsements/tier-1", headers=eud_header
    ).json()["data"]
    return endorsements
