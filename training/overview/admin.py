from django.contrib import admin
from django.contrib.admin.models import LogEntry

from .models import TraineeClaim


@admin.register(LogEntry)
class LogEntryAdmin(admin.ModelAdmin):
    list_display = (
        "action_time",
        "user",
        "content_type",
        "object_repr",
        "action_flag",
        "change_message",
    )
    list_filter = ("user", "action_flag")
    search_fields = ("object_repr", "change_message")

    def has_add_permission(self, request):
        return False  # Prevent manual addition


admin.site.register(TraineeClaim)
