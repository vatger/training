from django.db import models
from django.contrib.auth.models import User

from lists.models import Course


class TraineeClaim(models.Model):
    trainee = models.ForeignKey(
        User, on_delete=models.CASCADE, related_name="trainee_claims"
    )
    mentor = models.ForeignKey(
        User, on_delete=models.CASCADE, related_name="claim_mentor"
    )
    course = models.ForeignKey(
        Course, on_delete=models.CASCADE, related_name="claim_course"
    )
