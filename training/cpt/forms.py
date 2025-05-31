from django import forms
from django.contrib.auth.models import User
from lists.models import Course

from .models import CPT


class CPTForm(forms.ModelForm):
    class Meta:
        model = CPT
        fields = ["course", "trainee", "date", "examiner", "local"]
        widgets = {
            "course": forms.Select(
                attrs={
                    "class": "form-control",
                    "required": True,
                }
            ),
            "trainee": forms.Select(
                attrs={
                    "class": "form-control",
                    "required": True,
                }
            ),
            "date": forms.DateTimeInput(
                attrs={
                    "class": "form-control",
                    "type": "datetime-local",
                    "required": True,
                }
            ),
            "examiner": forms.Select(
                attrs={
                    "class": "form-control",
                }
            ),
            "local": forms.Select(
                attrs={
                    "class": "form-control",
                }
            ),
        }
        labels = {
            "course": "Course",
            "trainee": "Trainee",
            "date": "Date & Time",
            "examiner": "Examiner (Optional)",
            "local": "Local Mentor (Optional)",
        }
        help_texts = {
            "course": "Select the course for this CPT",
            "trainee": "Select the trainee for this CPT",
            "date": "Date and time of the CPT",
            "examiner": "Select an examiner qualified for this course position",
            "local": "Select a local mentor who is a mentor for this course",
        }

    def __init__(self, request, *args, **kwargs):
        super().__init__(*args, **kwargs)

        # Set up initial querysets
        self.fields["course"].queryset = request.user.mentored_courses.filter(
            type="RTG"
        ).order_by("name")
        if request.user.is_superuser:
            self.fields["course"].queryset = Course.objects.filter(type="RTG").order_by(
                "name"
            )

        self.fields["trainee"].queryset = User.objects.none()
        self.fields["examiner"].queryset = User.objects.none()
        self.fields["local"].queryset = User.objects.none()

        # Set empty labels for optional fields
        self.fields["examiner"].empty_label = "-- Select Examiner (Optional) --"
        self.fields["local"].empty_label = "-- Select Local Contact (Optional) --"

        # Make optional fields not required
        self.fields["examiner"].required = False
        self.fields["local"].required = False

        if request.POST:
            # Dynamically include submitted values
            try:
                trainee_id = request.POST["trainee"]
                if trainee_id:
                    self.fields["trainee"].queryset = User.objects.filter(id=trainee_id)

                examiner_id = request.POST["examiner"]
                if examiner_id:
                    self.fields["examiner"].queryset = User.objects.filter(
                        id=examiner_id
                    )

                local_id = request.POST["local"]
                if local_id:
                    self.fields["local"].queryset = User.objects.filter(id=local_id)
            except Exception:
                pass  # Optional: Log or raise depending on how strict you want this

    def clean_examiner(self):
        """Validate that examiner has the required position for the course"""
        examiner = self.cleaned_data.get("examiner")
        course = self.cleaned_data.get("course")

        if examiner and course:
            # Check if examiner has the required position for this course
            if not examiner.examiner.positions.filter(
                position=course.position
            ).exists():
                raise forms.ValidationError(
                    f"The selected examiner does not have the required position "
                    f"({course.position.name}) for this course."
                )

        return examiner

    def clean_local(self):
        """Validate that local contact is a mentor for the course"""
        local = self.cleaned_data.get("local")
        course = self.cleaned_data.get("course")

        if local and course:
            # Check if local contact is a mentor for this course
            if not course.mentors.filter(id=local.id).exists():
                raise forms.ValidationError(
                    "The selected local mentor is not a mentor for this course."
                )

        return local

    def clean(self):
        """Additional form validation"""
        cleaned_data = super().clean()
        trainee = cleaned_data.get("trainee")
        examiner = cleaned_data.get("examiner")
        local = cleaned_data.get("local")

        # Ensure trainee is not the same as examiner or local
        if trainee and examiner and trainee == examiner:
            raise forms.ValidationError("Trainee cannot be the same as the examiner.")

        if trainee and local and trainee == local:
            raise forms.ValidationError(
                "Trainee cannot be the same as the local contact."
            )

        # Ensure examiner is not the same as local
        if examiner and local and examiner == local:
            raise forms.ValidationError(
                "Examiner cannot be the same as the local mentor."
            )

        return cleaned_data


class CPTCreateForm(CPTForm):
    """Form specifically for creating new CPTs with required fields"""

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        # Ensure required fields are marked as such
        self.fields["course"].required = True
        self.fields["trainee"].required = True
        self.fields["date"].required = True
