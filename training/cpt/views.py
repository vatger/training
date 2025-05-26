from training.permissions import mentor_required

from .models import CPT


@mentor_required
def index(request):
    cpts = CPT.objects.filter(passed=None).order_by("date")


@mentor_required
def new_cpt(request):
    if request.method == "POST":
        # Handle form submission for creating a new CPT
