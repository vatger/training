from django.urls import path

from . import views


app_name = "trainee"

urlpatterns = [
    path("", views.home, name="home"),
    path("<int:vatsim_id>/", views.mentor_view, name="mentor_view"),
]
