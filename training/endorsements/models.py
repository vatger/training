from django.db import models


class EndorsementGroup(models.Model):
    name = models.CharField(max_length=15)

    def __str__(self):
        return self.name


class EndorsementActivity(models.Model):
    id = models.IntegerField(primary_key=True)
    activity = models.FloatField(default=0.0)
    updated = models.DateTimeField(auto_now=True)
    group = models.ForeignKey(EndorsementGroup, on_delete=models.CASCADE)
