from django.db import models
from django.contrib.auth.models import User


def get_name(self):
    return self.first_name + " " + self.last_name


User.add_to_class("__str__", get_name)


class UserDetail(models.Model):
    user = models.OneToOneField(User, on_delete=models.CASCADE)
    subdivision = models.CharField(max_length=10, blank=True, null=True)
    rating = models.IntegerField()

    def __str__(self):
        return self.user.first_name + " " + self.user.last_name
