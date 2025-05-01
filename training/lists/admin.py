from django.contrib import admin
from .models import Course, WaitingListEntry


class WaitingListEntryAdmin(admin.ModelAdmin):
    list_display = ("user_username", "course", "date_added", "activity")
    autocomplete_fields = ["user"]

    def user_username(self, obj):
        return obj.user.username

    user_username.short_description = "Username"


admin.site.register(Course)
admin.site.register(WaitingListEntry, WaitingListEntryAdmin)
