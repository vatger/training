from django.urls import path

from . import views

app_name = "familiarisations"

urlpatterns = [
    path("", views.familiarisations, name="familiarisations"),
]
