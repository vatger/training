from django.contrib import admin
from .models import EndorsementActivity, EndorsementGroup


class EndorsementActivityAdmin(admin.ModelAdmin):
    # List display configuration to show relevant fields in the list view
    list_display = ("id", "activity", "updated", "group", "formatted_updated")

    # Add filters to filter by activity, updated, and group
    list_filter = ("activity", "updated", "group")

    # Allow searching by activity, id, and group (by group name)
    search_fields = ("id", "activity", "group__name")

    # Customize the list view to be more readable (e.g., formatting updated time)
    def formatted_updated(self, obj):
        return obj.updated.strftime("%Y-%m-%d %H:%M:%S")

    formatted_updated.short_description = "Last Updated"

    # Define the fields displayed in the form view (include group field)
    fields = ("activity", "group", "removal_notified", "removal_date", "created")

    # read_only fields
    readonly_fields = ("created",)  # "removal_notified", "removal_date")

    # Add a simple action to reset activity values (optional)
    actions = ["reset_activity"]

    def reset_activity(self, request, queryset):
        # Example action to reset activity to 0
        queryset.update(activity=0.0)
        self.message_user(request, "Selected activities have been reset.")

    reset_activity.short_description = "Reset Activity to 0"


# Register the customized admin page
admin.site.register(EndorsementActivity, EndorsementActivityAdmin)
admin.site.register(EndorsementGroup)
