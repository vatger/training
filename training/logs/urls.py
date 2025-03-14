from django.urls import path

from . import views


app_name = "logs"

urlpatterns = [
    path(
        "new/<int:trainee_id>/<int:course_id>/",
        views.create_training_log,
        name="new_log",
    ),
    path("<int:log_id>/", views.log_detail, name="log_detail"),
]
