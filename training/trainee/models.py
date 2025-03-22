from django.db import models
from django.contrib.auth.models import User

# Create your models here.


class Comment(models.Model):
    user = models.ForeignKey(User, on_delete=models.CASCADE, related_name="comments")
    author = models.ForeignKey(User, on_delete=models.SET_NULL, null=True)
    text = models.TextField()
    date_added = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"{self.user.username}"
