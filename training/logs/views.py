from django.http import HttpResponseForbidden
from django.shortcuts import render, redirect, get_object_or_404
from django.contrib.auth.decorators import login_required
from django.contrib.auth.models import User

from .forms import TrainingLogForm
from lists.models import Course
from overview.models import TraineeClaim
from .models import Log

from training.permissions import mentor_required

@mentor_required
def create_training_log(request, trainee_id: int, course_id: int):
    # Ensure the trainee and course exist
    trainee = get_object_or_404(User, id=trainee_id)
    course = get_object_or_404(Course, id=course_id)

    if request.user not in course.mentors.all():
        return redirect("overview:overview")

    # Define all evaluation categories
    category_list = [
        {"name": "theory", "label": "Theory", "description": "Applies required knowledge including airspace structure, SOPs, LoAs."},
        {"name": "phraseology", "label": "Phraseology/Radiotelephony", "description": "Applies correct phraseology in English and German."},
        {"name": "coordination", "label": "Coordination", "description": "Performs the required coordination with neighboring stations clearly and effectively. Hands/takes over station correctly."},
        {"name": "tag_management", "label": "Tag Management/FPL Handling", "description": "Keeps flight plan and tag up to date at all times."},
        {"name": "situational_awareness", "label": "Situational Awareness", "description": "Aware of the current and future traffic situation. Takes new information into account."},
        {"name": "problem_recognition", "label": "Problem Recognition", "description": "Recognizes problems early and reacts accordingly."},
        {"name": "traffic_planning", "label": "Traffic Planning", "description": "Looks ahead and plans a secure and efficient traffic flow."},
        {"name": "reaction", "label": "Reaction", "description": "Reacts in a timely manner, flexible and appropriate to changes in the current traffic situation."},
        {"name": "separation", "label": "Separation", "description": "Applies prescribed separation minima at all times (i.e. runway, radar, wake turbulence, separation etc.)."},
        {"name": "efficiency", "label": "Efficiency", "description": "Takes pilot's requests into account, handles traffic in an efficient way for himself, the downstream sector and the pilot."},
        {"name": "ability_to_work_under_pressure", "label": "Ability to Work Under Pressure", "description": "Shows consistent performance regardless of traffic volume. Recovery from mistakes."},
        {"name": "motivation", "label": "Manner and Motivation", "description": "Is open to feedback and makes a realistic assessment of own performance. Deals respectfully with others and is well prepared for the session."},
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
            "categories": category_list,
            "trainee": trainee,
            "course": course,
        },
    )


@login_required
def log_detail(request, log_id):
    log = get_object_or_404(Log, pk=log_id)
    course = log.course
    
    is_own_log = request.user == log.trainee
    is_log_mentor = request.user == log.mentor
    is_course_mentor = course is not None and request.user in course.mentors.all()
    is_admin = request.user.is_superuser
    
    # If none of the permission conditions are met, deny access
    if not (is_own_log or is_log_mentor or is_course_mentor or is_admin):
        return HttpResponseForbidden("You do not have permission to view this log.")
    
    # Determine if the user can view internal remarks
    can_view_internal = is_log_mentor or is_course_mentor or is_admin
    
    # Define custom breadcrumbs for this view
    breadcrumbs = [
        {'title': 'Dashboard', 'url': '/'},
        {'title': 'Training Logs', 'url': '#'},
        {'title': f'{log.position} Training Log', 'url': None}
    ]
    
    return render(request, "logs/log_detail.html", {
        'form': log,
        'breadcrumbs': breadcrumbs,
        'render_internal': can_view_internal  # Only true for mentors and admins
    })