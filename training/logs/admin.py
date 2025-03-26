from django.contrib import admin
from django.utils.translation import gettext_lazy as _
from .models import Log


class LogAdmin(admin.ModelAdmin):
    list_display = ("session_date", "trainee", "mentor", "position", "type", "course")
    list_filter = ("session_date", "course", "trainee", "type")
    ordering = ("-session_date",)  # Sort by most recent session first
    search_fields = ("trainee__username", "mentor__username", "position")

    def get_queryset(self, request):
        """Limit logs to only those where the user is mentoring the course."""
        qs = super().get_queryset(request)
        if request.user.is_superuser:
            return qs  # Superusers see all logs
        # Filter logs where the course is in the user's mentored_courses
        return qs.filter(course__in=request.user.mentored_courses.all())

    def has_change_permission(self, request, obj=None):
        """Allow only users who mentor the course to edit the log."""
        if obj is None:  # List view
            return True
        # Check if the user is mentoring the course in the log
        return request.user in obj.course.mentors.all() or request.user.is_superuser

    def has_delete_permission(self, request, obj=None):
        """Allow only users who mentor the course to delete the log."""
        return self.has_change_permission(request, obj)


admin.site.register(Log, LogAdmin)
