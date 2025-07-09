import json

from django.contrib.auth.decorators import login_required
from django.contrib.auth.models import User
from django.http import HttpResponseForbidden
from django.shortcuts import render, redirect, get_object_or_404
from django.utils.safestring import mark_safe
from training.permissions import mentor_required

from lists.models import Course
from overview.models import TraineeClaim
from .forms import TrainingLogForm
from .models import Log


@mentor_required
def create_training_log(request, trainee_id: int, course_id: int):
    trainee = get_object_or_404(User, id=trainee_id)
    course = get_object_or_404(Course, id=course_id)

    if request.user not in course.mentors.all():
        return redirect("overview:overview")

    continue_draft = request.GET.get("continue", "false").lower() == "true"

    category_list = [
        {
            "name": "theory",
            "label": "Theory",
            "description": "Applies required knowledge including airspace structure, SOPs, LoAs.",
        },
        {
            "name": "phraseology",
            "label": "Phraseology/Radiotelephony",
            "description": "Applies correct phraseology in English and German.",
        },
        {
            "name": "coordination",
            "label": "Coordination",
            "description": "Performs the required coordination with neighboring stations clearly and effectively. Hands/takes over station correctly.",
        },
        {
            "name": "tag_management",
            "label": "Tag Management/FPL Handling",
            "description": "Keeps flight plan and tag up to date at all times.",
        },
        {
            "name": "situational_awareness",
            "label": "Situational Awareness",
            "description": "Aware of the current and future traffic situation. Takes new information into account.",
        },
        {
            "name": "problem_recognition",
            "label": "Problem Recognition",
            "description": "Recognizes problems early and reacts accordingly.",
        },
        {
            "name": "traffic_planning",
            "label": "Traffic Planning",
            "description": "Looks ahead and plans a secure and efficient traffic flow.",
        },
        {
            "name": "reaction",
            "label": "Reaction",
            "description": "Reacts in a timely manner, flexible and appropriate to changes in the current traffic situation.",
        },
        {
            "name": "separation",
            "label": "Separation",
            "description": "Applies prescribed separation minima at all times (i.e. runway, radar, wake turbulence, separation etc.).",
        },
        {
            "name": "efficiency",
            "label": "Efficiency",
            "description": "Takes pilot's requests into account, handles traffic in an efficient way for himself, the downstream sector and the pilot.",
        },
        {
            "name": "ability_to_work_under_pressure",
            "label": "Ability to Work Under Pressure",
            "description": "Shows consistent performance regardless of traffic volume. Recovery from mistakes.",
        },
        {
            "name": "motivation",
            "label": "Manner and Motivation",
            "description": "Is open to feedback and makes a realistic assessment of own performance. Deals respectfully with others and is well prepared for the session.",
        },
    ]

    if request.method == "POST":
        form = TrainingLogForm(request.POST)
        if form.is_valid():
            training_log = form.save(commit=False)
            training_log.trainee = trainee
            training_log.mentor = request.user
            training_log.course = course
            training_log.save()
            try:
                claim = TraineeClaim.objects.get(
                    trainee=trainee, course=course, mentor=request.user
                )
                claim.delete()
            except TraineeClaim.DoesNotExist:
                pass

            response = redirect("overview:overview")
            response.delete_cookie(f"log_draft_{trainee_id}_{course_id}")
            return response
    else:
        form = TrainingLogForm()

    draft_context = {
        "draft_key": f"log_draft_{trainee_id}_{course_id}",
        "continue_draft": "true" if continue_draft else "false",
        "should_clear_draft": "true" if not continue_draft else "false",
    }

    initial_values = {}

    return render(
        request,
        "logs/create_training_log.html",
        {
            "form": form,
            "categories": category_list,
            "trainee": trainee,
            "course": course,
            "edit_mode": False,
            "initial_values": initial_values,
            **draft_context,
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

    if not (is_own_log or is_log_mentor or is_course_mentor or is_admin):
        return HttpResponseForbidden("You do not have permission to view this log.")

    can_view_internal = is_log_mentor or is_course_mentor or is_admin

    breadcrumbs = [
        {"title": "Dashboard", "url": "/"},
        {"title": "Training Logs", "url": "#"},
        {"title": f"{log.position} Training Log", "url": None},
    ]

    return render(
        request,
        "logs/log_detail.html",
        {
            "form": log,
            "breadcrumbs": breadcrumbs,
            "render_internal": can_view_internal,
        },
    )


@mentor_required
def edit_training_log(request, log_id):
    log = get_object_or_404(Log, pk=log_id)

    if request.user != log.mentor and not request.user.is_superuser:
        return HttpResponseForbidden("You do not have permission to edit this log.")

    if (
        log.course
        and request.user not in log.course.mentors.all()
        and not request.user.is_superuser
    ):
        return HttpResponseForbidden(
            "You do not have permission to edit logs for this course."
        )

    if request.method == "POST":
        form = TrainingLogForm(request.POST, instance=log)
        if form.is_valid():
            updated_log = form.save()
            return redirect("logs:log_detail", log_id=updated_log.id)
        else:
            print(f"Form errors: {form.errors}")
    else:
        form = TrainingLogForm(instance=log)

    form_errors = {}
    if form.errors:
        for field_name, error_list in form.errors.items():
            form_errors[field_name] = error_list[0]  # Just take the first error

    safe_form_errors = mark_safe(json.dumps(form_errors))

    category_list = [
        {
            "name": "theory",
            "label": "Theory",
            "description": "Applies required knowledge including airspace structure, SOPs, LoAs.",
        },
        {
            "name": "phraseology",
            "label": "Phraseology/Radiotelephony",
            "description": "Applies correct phraseology in English and German.",
        },
        {
            "name": "coordination",
            "label": "Coordination",
            "description": "Performs the required coordination with neighboring stations clearly and effectively. Hands/takes over station correctly.",
        },
        {
            "name": "tag_management",
            "label": "Tag Management/FPL Handling",
            "description": "Keeps flight plan and tag up to date at all times.",
        },
        {
            "name": "situational_awareness",
            "label": "Situational Awareness",
            "description": "Aware of the current and future traffic situation. Takes new information into account.",
        },
        {
            "name": "problem_recognition",
            "label": "Problem Recognition",
            "description": "Recognizes problems early and reacts accordingly.",
        },
        {
            "name": "traffic_planning",
            "label": "Traffic Planning",
            "description": "Looks ahead and plans a secure and efficient traffic flow.",
        },
        {
            "name": "reaction",
            "label": "Reaction",
            "description": "Reacts in a timely manner, flexible and appropriate to changes in the current traffic situation.",
        },
        {
            "name": "separation",
            "label": "Separation",
            "description": "Applies prescribed separation minima at all times (i.e. runway, radar, wake turbulence, separation etc.).",
        },
        {
            "name": "efficiency",
            "label": "Efficiency",
            "description": "Takes pilot's requests into account, handles traffic in an efficient way for himself, the downstream sector and the pilot.",
        },
        {
            "name": "ability_to_work_under_pressure",
            "label": "Ability to Work Under Pressure",
            "description": "Shows consistent performance regardless of traffic volume. Recovery from mistakes.",
        },
        {
            "name": "motivation",
            "label": "Manner and Motivation",
            "description": "Is open to feedback and makes a realistic assessment of own performance. Deals respectfully with others and is well prepared for the session.",
        },
    ]

    initial_values = {}
    for field_name in dir(log):
        if (
            field_name.endswith("_positives")
            or field_name.endswith("_negatives")
            or field_name in ["internal_remarks", "final_comment"]
        ):
            value = getattr(log, field_name)
            if value:
                initial_values[field_name] = value
            else:
                initial_values[field_name] = ""

    form_data = {
        "session_date": (
            log.session_date.strftime("%Y-%m-%d") if log.session_date else ""
        ),
        "position": str(log.position) if log.position else "",
        "type": str(log.type) if log.type else "O",
        # Additional session details
        "traffic_level": str(log.traffic_level) if log.traffic_level else "",
        "traffic_complexity": (
            str(log.traffic_complexity) if log.traffic_complexity else ""
        ),
        "weather_conditions": (
            str(log.weather_conditions) if log.weather_conditions else ""
        ),
        "runway_configuration": (
            str(log.runway_configuration) if log.runway_configuration else ""
        ),
        "surrounding_stations": (
            str(log.surrounding_stations) if log.surrounding_stations else ""
        ),
        "session_duration": str(log.session_duration) if log.session_duration else "",
        "special_procedures": (
            str(log.special_procedures) if log.special_procedures else ""
        ),
        "airspace_restrictions": (
            str(log.airspace_restrictions) if log.airspace_restrictions else ""
        ),
        # Rating categories
        "theory": str(log.theory) if log.theory is not None else "0",
        "phraseology": str(log.phraseology) if log.phraseology is not None else "0",
        "coordination": str(log.coordination) if log.coordination is not None else "0",
        "tag_management": (
            str(log.tag_management) if log.tag_management is not None else "0"
        ),
        "situational_awareness": (
            str(log.situational_awareness)
            if log.situational_awareness is not None
            else "0"
        ),
        "problem_recognition": (
            str(log.problem_recognition) if log.problem_recognition is not None else "0"
        ),
        "traffic_planning": (
            str(log.traffic_planning) if log.traffic_planning is not None else "0"
        ),
        "reaction": str(log.reaction) if log.reaction is not None else "0",
        "separation": str(log.separation) if log.separation is not None else "0",
        "efficiency": str(log.efficiency) if log.efficiency is not None else "0",
        "ability_to_work_under_pressure": (
            str(log.ability_to_work_under_pressure)
            if log.ability_to_work_under_pressure is not None
            else "0"
        ),
        "motivation": str(log.motivation) if log.motivation is not None else "0",
        "next_step": str(log.next_step) if log.next_step else "",
        "result": "true" if log.result else "false" if log.result is not None else "",
    }

    safe_form_data = mark_safe(json.dumps(form_data))

    draft_context = {
        "draft_key": f"log_edit_{log_id}",
        "continue_draft": "false",
        "should_clear_draft": "false",
    }

    return render(
        request,
        "logs/create_training_log.html",
        {
            "form": form,
            "categories": category_list,
            "trainee": log.trainee,
            "course": log.course,
            "edit_mode": True,
            "log_id": log_id,
            "initial_values": initial_values,
            "safe_form_data": safe_form_data,
            "safe_form_errors": safe_form_errors,
            **draft_context,
        },
    )
