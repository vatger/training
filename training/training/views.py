from django.shortcuts import redirect
from django.contrib.auth.decorators import login_required

from connect.views import mentor_groups


@login_required
def group_based_redirect(request):
    if request.user.groups.filter(name__in=mentor_groups).exists():
        return redirect("overview:overview")
    else:
        return redirect("trainee:home")
