import os

import requests
from django.contrib.auth.models import User
from django.db import models
from django.utils import timezone
from dotenv import load_dotenv

from lists.models import Position, Course

load_dotenv()


def send_confirmed():
    header = {
        "Authorization": f"Token {os.getenv('VATGER_API_KEY')}",
        "Content-Type": "application/json",
        "Accept": "application/json",
    }
    cpts = CPT.objects.filter(confirmed=True, passed__isnull=True).order_by("date")
    res = []
    for cpt in cpts:
        res.append(
            {
                "trainee": cpt.trainee.get_full_name(),
                "date": f"{cpt.date.strftime('%d.%m.%y %H:%M')}lcl",
                "position": cpt.course.solo_station,
            }
        )
    data = {
        "text_data": "The above CPTs have been confirmed.",
        "table_data": res,
    }
    r = requests.post(
        "http://vatsim-germany.org/api/board/post/cpt", json=data, headers=header
    ).json()


def default_8pm_today():
    now = timezone.localtime()  # current time in local timezone
    return now.replace(hour=20, minute=0, second=0, microsecond=0)


class ExaminerPosition(models.Model):
    position = models.CharField(
        max_length=3,
        choices=[(tag.value, tag.label) for tag in Position if tag != Position.GND],
        unique=True,
    )

    def __str__(self):
        return self.get_position_display()


class Examiner(models.Model):
    user = models.OneToOneField(User, on_delete=models.CASCADE, related_name="examiner")
    positions = models.ManyToManyField(ExaminerPosition, related_name="examiners")
    callsign = models.CharField(max_length=10, unique=True)

    def __str__(self):
        return f"{self.user.get_full_name()} - {self.callsign}"


class CPT(models.Model):
    trainee = models.ForeignKey(User, on_delete=models.CASCADE, related_name="cpts")
    examiner = models.ForeignKey(
        User,
        on_delete=models.SET_NULL,
        related_name="examined_cpts",
        null=True,
        blank=True,
    )
    local = models.ForeignKey(
        User,
        on_delete=models.SET_NULL,
        related_name="local_cpts",
        null=True,
        blank=True,
    )
    date = models.DateTimeField(default=default_8pm_today)
    passed = models.BooleanField(null=True, blank=True, default=None)
    course = models.ForeignKey(
        Course, on_delete=models.SET_NULL, related_name="cpts", null=True, blank=True
    )
    confirmed = models.BooleanField(default=False)
    log_uploaded = models.BooleanField(default=False)

    def save(self, *args, **kwargs):
        send = False
        is_create = self.pk is None

        if is_create:
            if self.confirmed:
                send = True

        else:
            old = CPT.objects.get(pk=self.pk)
            if old.confirmed != self.confirmed:
                send = True

        super().save(*args, **kwargs)
        if send:
            send_confirmed()


class CPTLog(models.Model):
    cpt = models.ForeignKey(CPT, on_delete=models.CASCADE, related_name="log")
    log_file = models.FileField(upload_to="cpt_logs/")
    uploaded_by = models.ForeignKey(
        User, on_delete=models.CASCADE, related_name="cpt_logs_uploaded"
    )
    uploaded_at = models.DateTimeField(auto_now_add=True)
