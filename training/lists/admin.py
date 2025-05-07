from django.contrib import admin
from .models import Course, WaitingListEntry


class CourseAdmin(admin.ModelAdmin):
    search_fields = [
        "name",
        "airport_name",
    ]  # or whatever field you want to search by


class WaitingListEntryAdmin(admin.ModelAdmin):
    list_display = ("user_username", "course", "date_added", "activity")
    autocomplete_fields = ["user", "course"]
    readonly_fields = ()

    def user_username(self, obj):
        return obj.user.username

    user_username.short_description = "Username"


admin.site.register(Course, CourseAdmin)
admin.site.register(WaitingListEntry, WaitingListEntryAdmin)
