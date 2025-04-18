from django.contrib import admin
from django.contrib.auth.models import User
from .models import FamiliarisationSector, Familiarisation


class FamiliarisationAdmin(admin.ModelAdmin):
    list_display = ("user_username", "sector")
    autocomplete_fields = ["user"]

    def user_username(self, obj):
        return obj.user.username

    user_username.short_description = "Username"


class UserAdmin(admin.ModelAdmin):
    search_fields = ["username"]


# Unregister the default User admin and register our custom one
admin.site.unregister(User)
admin.site.register(User, UserAdmin)


class FamiliarisationSectorAdmin(admin.ModelAdmin):
    list_display = ("name", "fir")


admin.site.register(FamiliarisationSector, FamiliarisationSectorAdmin)
admin.site.register(Familiarisation, FamiliarisationAdmin)
