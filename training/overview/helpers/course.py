import functools
from datetime import datetime, timezone

import requests
from cachetools import TTLCache
from django.conf import settings
from training.eud_header import eud_header


def cached_with_refetch(cache):
    def decorator(func):
        @functools.wraps(func)
        def wrapper(*args, refetch=False, **kwargs):
            # Create cache key
            key = str(args) + str(sorted(kwargs.items()))

            if refetch and key in cache:
                del cache[key]

            if key not in cache:
                cache[key] = func(*args, **kwargs)

            return cache[key]

        return wrapper

    return decorator


@cached_with_refetch(cache=TTLCache(maxsize=1024, ttl=60 * 10))
def get_solos():
    if settings.USE_CORE_MOCK:
        solos = [
            {
                "id": 1,
                "user_cid": 1601613,
                "instructor_cid": 1439797,
                "position": "EDDL_APP",
                "expiry": "2025-09-20T00:00:00.000000Z",
                "max_days": 13,
                "position_days": 13,
                "facility": 9,
                "created_at": "2025-03-05T02:10:40.000000Z",
                "updated_at": "2025-04-07T19:47:47.000000Z",
            },
        ]
    else:
        solos = requests.get(
            "https://core.vateud.net/api/facility/endorsements/solo", headers=eud_header
        ).json()["data"]
    res = []
    for solo in solos:
        expiry_date = datetime.fromisoformat(solo["expiry"].replace("Z", "+00:00"))
        created_date = datetime.fromisoformat(solo["created_at"].replace("Z", "+00:00"))
        remaining_days = (expiry_date.date() - datetime.now(timezone.utc).date()).days
        delta = solo["max_days"] - (expiry_date - created_date).days
        res.append(
            {
                "id": solo["id"],
                "user_cid": solo["user_cid"],
                "position": solo["position"],
                "expiry": expiry_date,
                "remaining_days": remaining_days,
                "delta": delta,
                "position_days": solo["position_days"],
                "max_days": solo.get("max_days", 0),
            }
        )
    return res
