import os
from time import sleep

import requests
from django.core.management.base import BaseCommand
from endorsements.models import EndorsementGroup

stations = [
    "EDMM_CTR",
    "EDMM_RDG_CTR",
    "EDDM_GNDDEL",
    "EDDM_TWR",
    "EDDM_APP",
    "EDGG_KTG_CTR",
    "EDDK_TWR",
    "EDDK_APP",
    "EDDL_GNDDEL",
    "EDDL_TWR",
    "EDDL_APP",
    "EDDF_GNDDEL",
    "EDDF_APP",
    "EDWW_CTR",
    "EDWW_W_CTR",
    "EDDH_GNDDEL",
    "EDDH_TWR",
    "EDDH_APP",
    "EDDB_GNDDEL",
    "EDDB_TWR",
    "EDDB_APP",
    "EDDF_TWR",
]


class Command(BaseCommand):
    help = "Command to create endorsement groups"

    def handle(self, *args, **options):
        for station in stations:
            EndorsementGroup.objects.get_or_create(name=station)

        self.stdout.write(self.style.SUCCESS("Endorsement groups created"))
