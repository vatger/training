from django.urls import path

from . import views


app_name = "overview"

urlpatterns = [
    path("", views.overview, name="overview"),
    path("claim/<int:trainee_id>/<int:course_id>/", views.claim_trainee, name="claim"),
    path(
        "remove/<int:trainee_id>/<int:course_id>/", views.remove_trainee, name="remove"
    ),
    path("solo/<int:vatsim_id>/<int:course_id>/", views.add_solo, name="add_solo"),
    path(
        "finish/<int:trainee_id>/<int:course_id>/", views.finish_course, name="finish"
    ),
]
