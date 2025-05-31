from django.contrib import admin
from django.urls import path, include

from .views import group_based_redirect

urlpatterns = [
    path("admin/", admin.site.urls),
    path("api/", include("api.urls")),
    path("connect/", include("connect.urls")),
    path("cpt/", include("cpt.urls")),
    path("logs/", include("logs.urls")),
    path("overview/", include("overview.urls")),
    path("lists/", include("lists.urls")),
    path("trainee/", include("trainee.urls")),
    path("endorsements/", include("endorsements.urls")),
    path("familiarisations/", include("familiarisations.urls")),
    path("", group_based_redirect, name="group_redirect"),
]
