from django.urls import path

from . import views


app_name = "logs"

urlpatterns = [
    path("new/", views.create_training_log, name="new_log"),
    path("<int:log_id>/", views.log_detail, name="log_detail"),
]
