from django.urls import path

from . import views

app_name = "endorsements"

urlpatterns = [
    path("", views.overview, name="overview"),
    path("remove/<int:endorsement_id>/", views.remove_tier1, name="remove"),
    path("trainee/", views.trainee_view, name="trainee_view"),
]
