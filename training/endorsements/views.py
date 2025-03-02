from django.shortcuts import render
from .models import EndorsementGroup
from .helpers import get_tier1_endorsements


# TODO: mentor only
def overview(request):
    groups = request.user.mentored_courses.values_list("endorsement_groups", flat=True)
    groups = [group for group in groups if group is not None]

    return render(
        request,
        "endorsements/endorsements.html",
        {"groups": groups},
    )
