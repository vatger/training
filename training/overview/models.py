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

    def __str__(self):
        return f"{self.mentor.first_name} {self.mentor.last_name} claiming {self.trainee.first_name} {self.trainee.last_name} in {self.course}"
