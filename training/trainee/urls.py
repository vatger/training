from django.urls import path

from . import views


app_name = "trainee"

urlpatterns = [
    path("", views.home, name="home"),
]
