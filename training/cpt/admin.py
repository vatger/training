from django.contrib import admin

from .models import Examiner, ExaminerPosition, CPT


class ExaminerAdmin(admin.ModelAdmin):
    search_fields = ("user__username", "callsign")
    filter_horizontal = ("positions",)


admin.site.register(Examiner, ExaminerAdmin)
admin.site.register(ExaminerPosition)
admin.site.register(CPT)
