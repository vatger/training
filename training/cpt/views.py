from django.contrib import messages
from django.http import JsonResponse
from django.shortcuts import redirect, render
from lists.models import Course
from overview.helpers.trainee import get_core_theory_passed
from training.permissions import mentor_required

from .forms import CPTCreateForm
from .models import CPT, Examiner


def cpt_confirmed(cpt: CPT) -> bool:
    return cpt.examiner is not None and cpt.local is not None


@mentor_required
def index(request):
    cpts = CPT.objects.filter(passed=None).order_by("date")
    return render(request, "cpt/index.html", {"cpts": cpts})


@mentor_required
def create_cpt(request):
    if request.method == "POST":
        # Handle form submission for creating a new CPT
        form = CPTCreateForm(request.POST)
        if form.is_valid():
            cpt = form.save(commit=False)
            core_theory_passed = get_core_theory_passed(
                cpt.trainee.username, cpt.course.position
            )
            if False:  # core_theory_passed != CoreState.PASSED:
                messages.error(request, "Trainee has not passed the core theory exam.")
                return redirect("cpt:index")
            cpt.passed = None
            cpt.confirmed = cpt.examiner is not None and cpt.local is not None
            cpt.save()
            messages.success(request, "CPT created successfully.")
            # TODO: Notify examiners and mentors about the new CPT
            return redirect("cpt:index")
    else:
        # Display the form for creating a new CPT
        form = CPTCreateForm()

    return render(request, "cpt/create_cpt.html", {"form": form})


@mentor_required
def get_course_data(request):
    """AJAX view to get examiners and mentors for a course"""
    course_id = request.GET.get("course_id")

    if not course_id:
        return JsonResponse({"examiners": [], "mentors": []})

    try:
        course = Course.objects.get(id=course_id)

        examiners = Examiner.objects.filter(
            positions__position=course.position
        ).select_related("user")

        examiner_data = [
            {"id": examiner.user.id, "name": str(examiner)} for examiner in examiners
        ]

        mentors = course.mentors.filter(is_active=True)
        mentor_data = [
            {"id": mentor.id, "name": mentor.get_full_name() or mentor.username}
            for mentor in mentors
        ]

        return JsonResponse({"examiners": examiner_data, "mentors": mentor_data})

    except Course.DoesNotExist:
        return JsonResponse({"examiners": [], "mentors": []})


@mentor_required
def join_local(request, cpt_id: int):
    try:
        cpt = CPT.objects.get(id=cpt_id)
        if request.user == cpt.local:
            messages.error(request, "You are already registered as local.")
        elif request.user == cpt.examiner:
            messages.error(request, "You cannot be both local and examiner.")
        else:
            if request.user in cpt.course.mentors.all():
                cpt.local = request.user
                cpt.confirmed = cpt_confirmed(cpt)
                cpt.save()
                messages.success(request, "You have successfully joined as local.")
            else:
                messages.error(request, "You must be a mentor to join as local.")
    except CPT.DoesNotExist:
        messages.error(request, "CPT not found.")

    return redirect("cpt:index")


@mentor_required
def leave_local(request, cpt_id: int):
    try:
        cpt = CPT.objects.get(id=cpt_id)
        if request.user == cpt.local:
            cpt.local = None
            cpt.confirmed = cpt_confirmed(cpt)
            cpt.save()
            messages.success(request, "You have successfully left as local.")
        else:
            messages.error(request, "You are not registered as local.")
    except CPT.DoesNotExist:
        messages.error(request, "CPT not found.")

    return redirect("cpt:index")


@mentor_required
def join_examiner(request, cpt_id: int):
    try:
        cpt = CPT.objects.get(id=cpt_id)
        if request.user == cpt.examiner:
            messages.error(request, "You are already registered as examiner.")
        elif request.user == cpt.local:
            messages.error(request, "You cannot be both local and examiner.")
        else:
            if cpt.course.position in request.user.examiner.positions.values_list(
                "position", flat=True
            ):
                cpt.examiner = request.user
                cpt.confirmed = cpt_confirmed(cpt)
                cpt.save()
                messages.success(request, "You have successfully joined as examiner.")
            else:
                messages.error(request, "You must be an examiner to join as examiner.")
    except CPT.DoesNotExist:
        messages.error(request, "CPT not found.")

    return redirect("cpt:index")


@mentor_required
def leave_examiner(request, cpt_id: int):
    try:
        cpt = CPT.objects.get(id=cpt_id)
        if request.user == cpt.examiner:
            cpt.examiner = None
            cpt.confirmed = cpt_confirmed(cpt)
            cpt.save()
            messages.success(request, "You have successfully left as examiner.")
        else:
            messages.error(request, "You are not registered as examiner.")
    except CPT.DoesNotExist:
        messages.error(request, "CPT not found.")

    return redirect("cpt:index")
