from cachetools import cached, TTLCache

from connect.views import mentor_groups
from django.contrib.auth.decorators import login_required
from django.contrib.auth.models import User
from django.shortcuts import get_object_or_404, HttpResponseRedirect, reverse, redirect
from django.shortcuts import render
from familiarisations.helpers import get_familiarisations
from lists.models import Course
from logs.models import Log
from trainee.forms import UserDetailForm

from .forms import CommentForm
from overview.helpers import get_course_completion

from training.permissions import mentor_required

def split_active_inactive(logs, courses, trainee):
    active = {}
    inactive = {}
    active_courses = set(trainee.active_courses.all())

    non_active = courses - active_courses
    for course in active_courses:
        active[course] = logs.filter(course=course).order_by("-session_date")

    for course in non_active:
        inactive[course] = logs.filter(course=course).order_by("-session_date")

    return active, inactive


@cached(cache=TTLCache(maxsize=1024, ttl=60 * 60))
def get_moodles(user) -> list:
    active_courses = user.active_courses.all()
    moodles = []
    for course in active_courses:
        for moodle_id in course.moodle_course_ids:
            link = f"https://moodle.vatsim-germany.org/course/view.php?id={moodle_id}"
            passed = get_course_completion(user.username, moodle_id)
            moodles.append(
                {"course": course.name, "passed": passed, "id": moodle_id, "link": link}
            )
    return moodles


@login_required
def home(request):
    logs = Log.objects.filter(trainee=request.user).order_by("-session_date")
    # Get all courses from the logs
    courses = set(Course.objects.filter(log__in=logs))

    active, inactive = split_active_inactive(logs, courses, request.user)

    # Get required Moodle courses
    moodles = get_moodles(request.user)
    fams = get_familiarisations(request.user.username)
    
    return render(
        request,
        "trainee/home.html",
        {
            "active": active, 
            "inactive": inactive, 
            "moodles": moodles,
            "fams": fams
        },
    )


@mentor_required
def mentor_view(request, vatsim_id: int):
    trainee = get_object_or_404(User, username=vatsim_id)
    courses = request.user.mentored_courses.all()
    if request.user.is_superuser:
        courses = Course.objects.all()

    if (
        not request.user.groups.filter(name__in=mentor_groups).exists()
        and not request.user.is_superuser
    ):
        return redirect("/")
    # Get all logs for the trainee that are in the courses
    logs = Log.objects.filter(trainee=trainee, course__in=courses).order_by(
        "-session_date"
    )
    # Get all courses from the logs
    courses = set(Course.objects.filter(log__in=logs))
    active, inactive = split_active_inactive(logs, courses, trainee)

    comments = trainee.comments.all().order_by("-date_added")
    if request.method == "POST":
        form = CommentForm(request.POST)
        if form.is_valid():
            text = form.cleaned_data["text"]
            author = request.user
            trainee.comments.create(text=text, author=author)
            return HttpResponseRedirect(
                reverse("trainee:mentor_view", args=[trainee.username])
            )
    else:
        form = CommentForm()

    moodles = get_moodles(trainee)
    fams = get_familiarisations(trainee.username)
    
    # Get all courses the mentor can assign
    available_courses = Course.objects.filter(mentors=request.user)
    if request.user.is_superuser:
        available_courses = Course.objects.all()
    # Exclude courses the trainee is already in
    available_courses = available_courses.exclude(active_trainees=trainee)

    return render(
        request,
        "trainee/mentor_view.html",
        {
            "trainee": trainee,
            "active": active,
            "inactive": inactive,
            "comments": comments,
            "form": form,
            "moodles": moodles,
            "fams": fams,
            "available_courses": available_courses
        },
    )

@mentor_required
def find_user(request):
    if request.method == "POST":
        user_form = UserDetailForm(request.POST)
        if user_form.is_valid():
            user_id = user_form.cleaned_data["user_id"]
            # Check if the user exists
            if User.objects.filter(username=user_id).exists():
                # Redirect to the user_detail view
                return HttpResponseRedirect(
                    reverse("trainee:mentor_view", args=[user_id])
                )
            else:
                user_form.add_error("user_id", "User not found.")
    else:
        user_form = UserDetailForm()

    return render(request, "trainee/find_user.html", {"user_form": user_form})

