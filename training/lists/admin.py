from django.contrib import admin
from .models import Course, WaitingListEntry

admin.site.register(Course)
admin.site.register(WaitingListEntry)
