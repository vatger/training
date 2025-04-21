from django.shortcuts import render

from .models import Familiarisation


def familiarisations(request):
    # Get all familiarisations, grouped by user
    familiarisations = Familiarisation.objects.all()
    familiarisations_by_user = {}
    for familiarisation in familiarisations:
        user = familiarisation.user.username
        if user not in familiarisations_by_user:
            familiarisations_by_user[user] = ""
        familiarisations_by_user[user] += familiarisation.sector.name + ", "
    return render(
        request,
        "familiarisations/all_fam.html",
        {"fams": familiarisations_by_user},
    )
