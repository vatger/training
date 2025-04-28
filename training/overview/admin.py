from django.contrib import admin
from django.contrib.admin.models import LogEntry

from .models import TraineeClaim, TraineeRemark


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


class TraineeRemarkAdmin(admin.ModelAdmin):
    list_display = ('trainee', 'course', 'last_updated', 'last_updated_by')
    list_filter = ('course', 'last_updated_by')
    search_fields = ('trainee__username', 'trainee__first_name', 'trainee__last_name', 'remark')
    readonly_fields = ('last_updated', 'last_updated_by')


admin.site.register(TraineeClaim)
admin.site.register(TraineeRemark, TraineeRemarkAdmin)