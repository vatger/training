from django.contrib import admin

from .models import Examiner, ExaminerPosition, CPT


class ExaminerAdmin(admin.ModelAdmin):
    list_display = ("user_username",)
    search_fields = ("user__username", "callsign")
    filter_horizontal = ("positions",)
    autocomplete_fields = ["user"]

    def user_username(self, obj):
        return obj.user.username

    user_username.short_description = "Username"


admin.site.register(Examiner, ExaminerAdmin)
admin.site.register(ExaminerPosition)
admin.site.register(CPT)
