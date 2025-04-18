from django.db import models
from django.contrib.auth.models import User


class FIR(models.TextChoices):
    EDGG = "EDGG"
    EDMM = "EDMM"
    EDWW = "EDWW"


class FamiliarisationSector(models.Model):
    name = models.CharField(max_length=4)
    fir = models.CharField(max_length=4, choices=FIR.choices)

    def __str__(self):
        return self.name


class Familiarisation(models.Model):
    user = models.ForeignKey(User, on_delete=models.CASCADE)
    sector = models.ForeignKey(FamiliarisationSector, on_delete=models.CASCADE)

    class Meta:
        unique_together = ("user", "sector")

    def __str__(self):
        return f"{self.user.first_name} {self.user.last_name} - {self.sector.name}"
