from django.urls import path

from . import views

app_name = "endorsements"

urlpatterns = [
    path("", views.overview, name="overview"),
    path("remove/<int:endorsement_id>/", views.remove_tier1, name="remove"),
    path("trainee/", views.trainee_view, name="trainee_view"),
    path(
        "request_tier_2/<int:endorsement_id>/",
        views.request_tier_2,
        name="request_tier_2",
    ),
]
