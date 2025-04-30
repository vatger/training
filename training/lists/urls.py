from django.urls import path

from . import views

app_name = "lists"

urlpatterns = [
    path("", views.view_lists, name="view_lists"),
    path("<int:course_id>/", views.join_leave_list, name="join_leave_list"),
    path("mentor", views.mentor_view, name="mentor_view"),
    path("start/<int:waitlist_entry_id>/", views.start_training, name="start_training"),
    path("remove_trainee/<int:waitlist_entry_id>/", views.remove_trainee, name="remove_trainee"),
]