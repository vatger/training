import os

import requests
from django.contrib import messages
from django.http import JsonResponse
from django.shortcuts import redirect, render
from django.utils import timezone
from dotenv import load_dotenv
from lists.models import Course
from overview.helpers.trainee import get_core_theory_passed, CoreState
from training.eud_header import eud_header
from training.permissions import mentor_required

from .forms import CPTCreateForm, DocumentForm
from .models import CPT, Examiner, CPTLog

load_dotenv()


def upload_log(request, cpt: CPT):
    link = f"https://core.vateud.net/api/facility/user/{int(cpt.trainee.username)}/notes/cpt"
    # Add file to multipart form data

    data = {
        "examiner_cid": int(cpt.examiner.username),
        "position": cpt.course.solo_station,
        "note": "See log",
        "cpt_pass": int(cpt.passed),
        "file": cpt.log.order_by("-uploaded_at")[0].log_file.name,
    }
    files = {
        "file": (
            cpt.log.order_by("-uploaded_at")[0].log_file.name,
            open(cpt.log.order_by("-uploaded_at")[0].log_file.path, "rb"),
        )
    }
    return requests.post(link, data=data, headers=eud_header, files=files).json()


def request_upgrade(request, cpt):
    link = (
        f"https://core.vateud.net/api/facility/user/{int(cpt.trainee.username)}/upgrade"
    )
    data = {
        "instructor_cid": int(request.user.username),
        "new_rating": cpt.trainee.userdetail.rating + 1,
    }
    return requests.post(link, data=data, headers=eud_header).json()


def inform_mentor(vatsim_id: int, cpt: CPT):
    data = {
        "title": "Available CPT",
        "message": f"A new CPT is available: {cpt.course.solo_station} on {cpt.date.strftime('%d.%m.%Y at %H:%M')}lcl.",
        "source_name": "VATGER ATD",
        "link_text": "Training Centre",
        "link_url": "https://training.vatsim-germany.org/cpt",
        "via": "board.ping",
    }
    header = {"Authorization": f"Token {os.getenv('VATGER_API_KEY')}"}
    r = requests.post(
        f"http://vatsim-germany.org/api/user/{vatsim_id}/send_notification",
        data=data,
        headers=header,
    )


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
        form = CPTCreateForm(request, request.POST)
        if form.is_valid():
            cpt = form.save(commit=False)
            core_theory_passed = get_core_theory_passed(
                cpt.trainee.username, cpt.course.position
            )
            if core_theory_passed != CoreState.PASSED:
                messages.error(request, "Trainee has not passed the core theory exam.")
                return redirect("cpt:index")
            cpt.passed = None
            cpt.confirmed = cpt.examiner is not None and cpt.local is not None
            cpt.save()
            messages.success(request, "CPT created successfully.")
            notify_set = set()
            if not cpt.examiner:
                possible_examiners = Examiner.objects.filter(
                    positions__position=cpt.course.position,
                )
                for examiner in possible_examiners:
                    notify_set.add(examiner.user)
            if not cpt.local:
                possible_mentors = cpt.course.mentors.all()
                for mentor in possible_mentors:
                    notify_set.add(mentor)

            for user in notify_set:
                inform_mentor(int(user.username), cpt)

            return redirect("cpt:index")
    else:
        # Display the form for creating a new CPT
        form = CPTCreateForm(request)

    return render(request, "cpt/create_cpt.html", {"form": form})


@mentor_required
def get_course_data(request):
    """AJAX view to get examiners and mentors for a course"""
    course_id = request.GET.get("course_id")
    date = timezone.datetime.fromisoformat(request.GET.get("date"))
    date = timezone.make_aware(date, timezone.get_current_timezone())

    if not course_id:
        return JsonResponse({"examiners": [], "mentors": []})

    try:
        course = Course.objects.get(id=course_id)

        examiners = Examiner.objects.filter(
            positions__position=course.position
        ).select_related("user")

        if date - timezone.now() > timezone.timedelta(hours=36):
            # Filter examiners who are also mentors of the course
            examiners = examiners.exclude(user__in=course.mentors.all())

        trainees = course.active_trainees.all().order_by("username")
        trainee_data = [
            {
                "id": trainee.id,
                "name": trainee.get_full_name(),
                "username": trainee.username,
            }
            for trainee in trainees
        ]

        examiner_data = [
            {"id": examiner.user.id, "name": str(examiner)} for examiner in examiners
        ]

        mentors = course.mentors.filter(is_active=True)
        mentor_data = [
            {"id": mentor.id, "name": mentor.get_full_name() or mentor.username}
            for mentor in mentors
        ]

        return JsonResponse(
            {
                "examiners": examiner_data,
                "mentors": mentor_data,
                "trainees": trainee_data,
            }
        )

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
        else:
            messages.error(request, "You are not registered as local.")
    except CPT.DoesNotExist:
        messages.error(request, "CPT not found.")

    return redirect("cpt:index")


@mentor_required
def join_examiner(request, cpt_id: int):
    try:
        request.user.examiner
    except:
        messages.error(request, "You must be an examiner to join as examiner.")
        return redirect("cpt:index")
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
                if (
                    request.user in cpt.course.mentors.all()
                    and cpt.date - timezone.now() > timezone.timedelta(hours=36)
                ):
                    messages.error(
                        request,
                        "You cannot join as examiner for mentored course if CPT is more than 36 hours away.",
                    )
                    return redirect("cpt:index")
                cpt.examiner = request.user
                cpt.confirmed = cpt_confirmed(cpt)
                cpt.save()
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
        else:
            messages.error(request, "You are not registered as examiner.")
    except CPT.DoesNotExist:
        messages.error(request, "CPT not found.")

    return redirect("cpt:index")


@mentor_required
def delete_cpt(request, cpt_id: int):
    try:
        cpt = CPT.objects.get(id=cpt_id)
        if request.user not in cpt.course.mentors.all():
            messages.error(request, "You do not have permission to delete this CPT.")
            return redirect("cpt:index")
        if cpt.passed is not None:
            messages.error(request, "Cannot delete a passed CPT.")
        else:
            cpt.delete()
            messages.success(request, "CPT deleted successfully.")
    except CPT.DoesNotExist:
        messages.error(request, "CPT not found.")

    return redirect("cpt:index")


@mentor_required
def grade_cpt(request, cpt_id: int, pass_fail: int):
    if not request.user.is_authenticated:
        messages.error(request, "You must be ATD to grade a CPT.")
        return redirect("cpt:index")
    try:
        cpt = CPT.objects.get(id=cpt_id)
        if pass_fail == 1:
            cpt.passed = True
            cpt.save()
            messages.success(request, "CPT graded as passed.")
        elif pass_fail == 0:
            cpt.passed = False
            cpt.save()
            messages.success(request, "CPT graded as failed.")
        else:
            messages.error(request, "Invalid grading option.")
            return redirect("cpt:index")
        if cpt.log_uploaded:
            upload_log(request, cpt)
            if cpt.passed:
                request_upgrade(request, cpt)
    except CPT.DoesNotExist:
        messages.error(request, "CPT not found.")
    return redirect("cpt:index")


@mentor_required
def upload_pdf(request, cpt_id: int):
    try:
        cpt = CPT.objects.get(id=cpt_id)
    except CPT.DoesNotExist:
        messages.error(request, "CPT not found.")
        return redirect("cpt:index")
    if request.method == "POST":
        form = DocumentForm(request.POST, request.FILES)
        if form.is_valid():
            log = form.save(commit=False)
            log.cpt = cpt
            log.uploaded_by = request.user
            log.save()
            cpt.log_uploaded = True
            cpt.save()
            return redirect("cpt:upload_pdf", cpt_id=cpt.id)
    else:
        form = DocumentForm()
    if (
        not request.user.is_superuser
        and request.user != cpt.local
        and request.user != cpt.examiner
    ):
        messages.error(request, "You do not have permission to upload documents.")
        return redirect("cpt:index")
    documents = CPTLog.objects.all().order_by("-uploaded_at").filter(cpt__id=cpt_id)
    return render(
        request,
        "cpt/upload_pdf.html",
        {"form": form, "documents": documents, "cpt": cpt},
    )
