from django.contrib import admin
from django.urls import path, include
from django.views.generic.base import RedirectView


urlpatterns = [
    path("admin/", admin.site.urls),
    path("logs/", include("logs.urls")),
    path("overview/", include("overview.urls")),
    path("lists/", include("lists.urls")),
    path("", RedirectView.as_view(url="/lists/")),
]
