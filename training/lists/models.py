from django.db import models
from django.contrib.auth.models import User, Group
from endorsements.models import EndorsementGroup


class Course(models.Model):
    class Rating(models.IntegerChoices):
        OBS = 1
        S1 = 2
        S2 = 3
        S3 = 4
        C1 = 5
        C2 = 6
        C3 = 7
        I1 = 8
        I2 = 9
        I3 = 10
        UNL = 1000

    class CourseType(models.TextChoices):
        EDMT = "EDMT", "Endorsement"
        RTG = "RTG", "Rating"
        GST = "GST", "Visitor"

    class Position(models.TextChoices):
        GND = "GND", "Ground"
        TWR = "TWR", "Tower"
        APP = "APP", "Approach"
        CTR = "CTR", "Centre"

    name = models.CharField(max_length=100)
    description = models.TextField(null=True, blank=True)
    airport_name = models.CharField(max_length=100)
    airport_icao = models.CharField(max_length=4)
    solo_station = models.CharField(max_length=15, null=True, blank=True)
    mentor_group = models.ForeignKey(Group, on_delete=models.SET_NULL, null=True)
    min_rating = models.IntegerField(choices=Rating.choices)
    max_rating = models.IntegerField(choices=Rating.choices)
    active_trainees = models.ManyToManyField(
        User, related_name="active_courses", blank=True
    )
    mentors = models.ManyToManyField(User, related_name="mentored_courses", blank=True)
    type = models.CharField(max_length=4, choices=CourseType.choices)
    position = models.CharField(max_length=3, choices=Position.choices)
    endorsement_groups = models.ManyToManyField(
        EndorsementGroup, blank=True, related_name="courses"
    )
    moodle_course_ids = models.JSONField(default=list, blank=True)

    def __str__(self):
        return self.name + " - " + self.type


class WaitingListEntry(models.Model):
    user = models.ForeignKey(User, on_delete=models.CASCADE)
    course = models.ForeignKey(Course, on_delete=models.CASCADE)
    date_added = models.DateTimeField(auto_now_add=True)
    activity = models.FloatField(default=0)
    hours_updated = models.DateTimeField(auto_now=True)

    def __str__(self):
        return f"{self.user.first_name} {self.user.last_name} - {self.course.name}"
