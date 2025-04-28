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

class TraineeRemark(models.Model):
    """
    Model to store mentor remarks for trainees. This allows mentors to add notes about a trainee's availability, performance, or other relevant information.
    """
    trainee = models.ForeignKey(User, on_delete=models.CASCADE, related_name="course_remarks")
    course = models.ForeignKey(Course, on_delete=models.CASCADE, related_name="trainee_remarks")
    remark = models.TextField(blank=True, null=True)
    last_updated = models.DateTimeField(auto_now=True)
    last_updated_by = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, related_name="updated_remarks")

    class Meta:
        unique_together = ('trainee', 'course')

    def __str__(self):
        return f"Remark for {self.trainee.get_full_name()} in {self.course.name}"