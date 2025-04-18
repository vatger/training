from django.contrib import admin

from .models import Familiarisation, FamiliarisationSector


# Register your models here.
@admin.register(Familiarisation)
class FamiliarisationAdmin(admin.ModelAdmin):
    list_display = ("user", "sector")
    search_fields = ("user__username", "sector__name")
    list_filter = ("sector__fir",)
    ordering = ("user__username", "sector__name")


admin.site.register(FamiliarisationSector)
