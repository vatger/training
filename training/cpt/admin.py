from django.contrib import admin

from .models import Examiner, ExaminerPosition, CPT, CPTLog


class ExaminerAdmin(admin.ModelAdmin):
    list_display = ("user_username", "full_name", "callsign")
    search_fields = ("user__username", "callsign")
    filter_horizontal = ("positions",)
    autocomplete_fields = ["user"]

    def full_name(self, obj):
        return f"{obj.user.first_name} {obj.user.last_name}".strip()

    full_name.short_description = "Full Name"

    def user_username(self, obj):
        return obj.user.username

    user_username.short_description = "Username"


class CPTAdmin(admin.ModelAdmin):
    list_display = ("course", "trainee", "date", "examiner", "local")
    search_fields = ("course__name", "trainee__user__username")
    list_filter = ("course", "date", "examiner", "local")
    autocomplete_fields = ["course", "trainee", "examiner", "local"]

    def get_queryset(self, request):
        qs = super().get_queryset(request)
        return qs.select_related("course", "trainee", "examiner", "local")


admin.site.register(Examiner, ExaminerAdmin)
admin.site.register(ExaminerPosition)
admin.site.register(CPT, CPTAdmin)
admin.site.register(CPTLog)
