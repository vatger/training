import os
from time import sleep

from django.core.management.base import BaseCommand
from django.utils import timezone
from endorsements.helpers import get_tier1_endorsements
from endorsements.models import EndorsementActivity
from endorsements.views import valid_removal

from .update_activity import get_hours


class Command(BaseCommand):
    help = "Remove endorsements"

    def handle(self, *args, **options):
        t1 = get_tier1_endorsements()
        endorsements = EndorsementActivity.objects.filter(
            removal_date__isnull=False,
            removal_date__lt=timezone.now(),
            removal_notified=True,
        )
        for endorsement in endorsements:
            if not valid_removal(endorsement):
                continue
            try:
                tier1_entry = next(item for item in t1 if item["id"] == endorsement.id)
            except StopIteration:
                self.stdout.write(self.style.WARNING("Endorsement not found"))
                endorsement.delete()
            hours = get_hours(tier1_entry)
            while hours == -1:
                # Wait for VATSIM API (:
                sleep(15)
                hours = get_hours(tier1_entry)
            if hours < float(os.getenv("T1_MIN_HOURS")):
                self.stdout.write(
                    self.style.SUCCESS(f"Endorsement {tier1_entry} removed")
                )
                # TODO: Add VATEUD endpoint
                # endorsement.delete()
