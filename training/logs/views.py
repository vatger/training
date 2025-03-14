from django.shortcuts import render, redirect, get_object_or_404
from django.contrib.auth.decorators import login_required
from django.contrib.auth.models import User

from .forms import TrainingLogForm
from lists.models import Course
from overview.models import TraineeClaim
from .models import Log


@login_required
def create_training_log(request, trainee_id: int, course_id: int):
    # Ensure the trainee and course exist
    trainee = get_object_or_404(User, id=trainee_id)
    course = get_object_or_404(Course, id=course_id)

    if request.user not in course.mentors.all():
        return redirect("overview:overview")

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
            training_log.trainee = trainee
            training_log.mentor = request.user  # Mentor is always the logged-in user
            training_log.course = course
            training_log.save()
            try:
                claim = TraineeClaim.objects.get(
                    trainee=trainee, course=course, mentor=request.user
                )
                claim.delete()
            except TraineeClaim.DoesNotExist:
                pass
            return redirect("overview:overview")
    else:
        form = TrainingLogForm()

    return render(
        request,
        "logs/create_training_log.html",
        {
            "form": form,
            "category_list": category_list,
            "trainee": trainee,
            "course": course,
        },
    )


@login_required
def log_detail(request, log_id):
    log = get_object_or_404(Log, pk=log_id)
    course = log.course
    if request.user not in course.mentors.all():
        return redirect("overview:overview")
    return render(request, "logs/log_detail.html", {"form": log})
