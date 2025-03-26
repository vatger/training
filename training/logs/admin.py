from django.contrib import admin
from django.utils.translation import gettext_lazy as _
from .models import Log


class LogAdmin(admin.ModelAdmin):
    list_display = ("session_date", "trainee", "mentor", "position", "type", "course")
    list_filter = ("session_date", "course", "trainee", "type")
    ordering = ("-session_date",)  # Sort by most recent session first
    search_fields = ("trainee__username", "mentor__username", "position")

    def get_queryset(self, request):
        """Limit logs to only those where the user is the mentor."""
        qs = super().get_queryset(request)
        if request.user.is_superuser:
            return qs  # Superusers see all logs
        return qs.filter(mentor=request.user)  # Regular users only see their logs

    def has_change_permission(self, request, obj=None):
        """Allow only mentors (or superusers) to edit logs."""
        if obj is None:  # List view
            return True
        return request.user == obj.mentor or request.user.is_superuser

    def has_delete_permission(self, request, obj=None):
        """Allow only mentors (or superusers) to delete logs."""
        return self.has_change_permission(request, obj)


admin.site.register(Log, LogAdmin)
