from django.db import models
from datetime import datetime


class EndorsementGroup(models.Model):
    name = models.CharField(max_length=15)

    def __str__(self):
        return self.name


class EndorsementActivity(models.Model):
    id = models.IntegerField(primary_key=True)
    activity = models.FloatField(default=0.0)
    updated = models.DateTimeField(default=datetime(2000, 1, 1, 0, 0, 0))
    group = models.ForeignKey(EndorsementGroup, on_delete=models.CASCADE)
    removal_date = models.DateField(null=True, blank=True)
    removal_notified = models.BooleanField(default=False)
    created = models.DateTimeField(default=datetime.now)
