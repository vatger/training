from django.urls import path

from . import views

app_name = "endorsements"

urlpatterns = [
    path("", views.overview, name="overview"),
]
