from django.db import models
from datetime import datetime


class RosterEntry(models.Model):
    user_id = models.IntegerField()
    last_session = models.DateTimeField(default=datetime(1970, 1, 1, 0, 0, 0))
    removal_date = models.DateTimeField(default=None, null=True)

    def __str__(self):
        return f"{self.user_id}"
