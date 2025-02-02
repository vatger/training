from django.shortcuts import render, redirect, get_object_or_404

from .forms import TrainingLogForm
from lists.models import Course
from .models import Log


def create_training_log(request):
    category_list = [
        {"name": "theory", "label": "Theory"},
        {"name": "phraseology", "label": "Phraseology"},
        {"name": "coordination", "label": "Coordination"},
        {"name": "tag_management", "label": "Tag Management"},
        {"name": "situational_awareness", "label": "Situational Awareness"},
        {"name": "traffic_flow", "label": "Traffic Flow"},
        {"name": "separation", "label": "Separation"},
        {
            "name": "ability_to_work_under_pressure",
            "label": "Ability to Work Under Pressure",
        },
        {"name": "motivation", "label": "Motivation"},
    ]

    if request.method == "POST":
        form = TrainingLogForm(request.POST)
        if form.is_valid():
            training_log = form.save(commit=False)
            training_log.trainee = request.user
            training_log.mentor = request.user
            training_log.course = Course.objects.get(name="Frankfurt Tower")
            training_log.save()
            return redirect(
                "success_page"
            )  # Replace with your success page or redirect target
    else:
        form = TrainingLogForm()

    return render(
        request,
        "logs/create_training_log.html",
        {"form": form, "category_list": category_list},
    )


# TODO: mentor only
def log_detail(request, log_id):
    log = get_object_or_404(Log, pk=log_id)
    return render(request, "logs/log_detail.html", {"form": log})
