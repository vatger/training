from django.urls import path

from . import views

app_name = "cpt"

urlpatterns = [
    path("", views.index, name="index"),
    path("create/", views.create_cpt, name="create_cpt"),
    path("local/join/<int:cpt_id>/", views.join_local, name="join_local"),
    path("local/leave/<int:cpt_id>/", views.leave_local, name="leave_local"),
    path("examiner/join/<int:cpt_id>/", views.join_examiner, name="join_examiner"),
    path("examiner/leave/<int:cpt_id>/", views.leave_examiner, name="leave_examiner"),
    path("delete/<int:cpt_id>/", views.delete_cpt, name="delete_cpt"),
    path("grade/<int:cpt_id>/<int:pass_fail>/", views.grade_cpt, name="grade_cpt"),
    path("api/course-data/", views.get_course_data, name="get_course_data"),
]
