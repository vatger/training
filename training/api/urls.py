# urls.py
from django.urls import path
from .views import user_delete_view, user_retrieve_view, tier1_endorsements, familiarisations, solos

urlpatterns = [
    path("gdpr-removal/<int:vatsim_id>", user_delete_view, name="user-delete"),
    path("user-data/<int:vatsim_id>", user_retrieve_view, name="user-retrieve"),
    path("tier1", tier1_endorsements, name="tier1"),
    path("familiarisations", familiarisations, name="familiarisations"),
    path("solos", solos, name="solos")
]
