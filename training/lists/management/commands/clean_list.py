import os

import requests
from django.contrib.admin.models import DELETION
from django.contrib.auth.models import User
from django.core.management.base import BaseCommand
from dotenv import load_dotenv
from lists.helpers import course_valid_for_user
from lists.models import WaitingListEntry
from training.helpers import log_admin_action

load_dotenv()


class Command(BaseCommand):
    help = "Daily command to clean waiting list"

    def handle(self, *args, **options):
        # Get all entries
        entries = WaitingListEntry.objects.all()
        for entry in entries:
            valid, reason = course_valid_for_user(entry.course, entry.user)
            if not valid:
                print(f"Deleting {entry} as it is invalid")
                entry.delete()
                log_admin_action(
                    User.objects.get(username=os.getenv("ATD_LEAD_CID")),
                    entry,
                    DELETION,
                    f"Removed {entry}, date added: {entry.date_added}.",
                )

                data = {
                    "title": "Waiting List Removal",
                    "message": f"You have been removed from the waiting list for the {entry.course.name} course. {reason} If you have any questions, please contact the VATGER ATD.",
                    "source_name": "VATGER ATD",
                }
                header = {"Authorization": f"Token {os.getenv("VATGER_API_KEY")}"}
                r = requests.post(
                    f"http://vatsim-germany.org/api/user/{entry.user.username}/send_notification",
                    data=data,
                    headers=header,
                )
