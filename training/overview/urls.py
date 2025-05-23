from django.urls import path

from overview.views import (
    overview,
    manage_mentors,
    remove_mentor,
    claim_trainee,
    remove_trainee,
    finish_course,
    update_remark,
    add_solo,
    delete_solo,
    assign_core_test_view,
)

app_name = "overview"

urlpatterns = [
    path("", overview, name="overview"),
    path("claim/<int:trainee_id>/<int:course_id>/", claim_trainee, name="claim"),
    path("remove/<int:trainee_id>/<int:course_id>/", remove_trainee, name="remove"),
    path("solo/<int:vatsim_id>/<int:course_id>/", add_solo, name="add_solo"),
    path("solo/delete/<int:solo_id>/", delete_solo, name="delete_solo"),
    path("finish/<int:trainee_id>/<int:course_id>/", finish_course, name="finish"),
    path("mentors/<int:course_id>/", manage_mentors, name="manage_mentors"),
    path(
        "remove_mentor/<int:course_id>/<int:mentor_id>/",
        remove_mentor,
        name="remove_mentor",
    ),
    path(
        "assign/<int:vatsim_id>/<int:course_id>",
        assign_core_test_view,
        name="assign_course",
    ),
    path(
        "remark/<int:trainee_id>/<int:course_id>/", update_remark, name="update_remark"
    ),
]
