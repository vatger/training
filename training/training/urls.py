from django.contrib import admin
from django.urls import path, include
from django.views.generic.base import RedirectView


urlpatterns = [
    path("admin/", admin.site.urls),
    path("api/", include("api.urls")),
    path("connect/", include("connect.urls")),
    path("logs/", include("logs.urls")),
    path("overview/", include("overview.urls")),
    path("lists/", include("lists.urls")),
    path("trainee/", include("trainee.urls")),
    path("endorsements/", include("endorsements.urls")),
    path("", RedirectView.as_view(url="/trainee/")),
]
