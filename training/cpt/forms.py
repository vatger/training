from django import forms
from django.contrib.auth.models import User

from .models import CPT, Course


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
            "local": "Local Contact (Optional)",
        }
        help_texts = {
            "course": "Select the course for this CPT",
            "trainee": "Select the trainee for this CPT",
            "date": "Date and time of the CPT",
            "examiner": "Select an examiner qualified for this course position",
            "local": "Select a local contact who is a mentor for this course",
        }

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)

        # Set up initial querysets
        self.fields["course"].queryset = Course.objects.all().order_by("name")
        self.fields["trainee"].queryset = User.objects.filter(is_active=True).order_by(
            "username"
        )

        # Initially empty querysets for examiner and local - will be populated via AJAX or form logic
        self.fields["examiner"].queryset = User.objects.all()
        self.fields["local"].queryset = User.objects.all()

        # Set empty labels for optional fields
        self.fields["examiner"].empty_label = "-- Select Examiner (Optional) --"
        self.fields["local"].empty_label = "-- Select Local Contact (Optional) --"

        # Make optional fields not required
        self.fields["examiner"].required = False
        self.fields["local"].required = False

        # If we have a course selected (e.g., when editing), filter the examiner and local querysets
        # if "course" in self.data:
        #     try:
        #         course_id = int(self.data.get("course"))
        #         self.fields["examiner"].queryset = self.get_qualified_examiners(
        #             course_id
        #         )
        #         self.fields["local"].queryset = self.get_course_mentors(course_id)
        #     except (ValueError, TypeError):
        #         pass
        # elif self.instance.pk and self.instance.course:
        #     self.fields["examiner"].queryset = self.get_qualified_examiners(
        #         self.instance.course.id
        #     )
        #     self.fields["local"].queryset = self.get_course_mentors(
        #         self.instance.course.id
        #     )

    # def get_qualified_examiners(self, course_id):
    #     """Get examiners who have the required position for the course"""
    #     try:
    #         course = Course.objects.get(id=course_id)
    #         # Assuming Course has a 'position' field that relates to ExaminerPosition
    #         # Adjust this query based on your actual Course model structure
    #         return (
    #             Examiner.objects.filter(positions=course.position)
    #             .select_related("user")
    #             .order_by("user__first_name", "user__last_name")
    #         )
    #     except Course.DoesNotExist:
    #         return Examiner.objects.none()
    #
    # def get_course_mentors(self, course_id):
    #     """Get users who are mentors for the course"""
    #     try:
    #         course = Course.objects.get(id=course_id)
    #         # Assuming Course has a 'mentors' field
    #         # Adjust this query based on your actual Course model structure
    #         return course.mentors.filter(is_active=True).order_by(
    #             "first_name", "last_name"
    #         )
    #     except Course.DoesNotExist:
    #         return User.objects.none()
    #
    # def clean_examiner(self):
    #     """Validate that examiner has the required position for the course"""
    #     examiner = self.cleaned_data.get("examiner")
    #     course = self.cleaned_data.get("course")
    #
    #     if examiner and course:
    #         # Check if examiner has the required position for this course
    #         if not examiner.positions.filter(id=course.position.id).exists():
    #             raise forms.ValidationError(
    #                 f"The selected examiner does not have the required position "
    #                 f"({course.position.name}) for this course."
    #             )
    #
    #     return examiner
    #
    # def clean_local(self):
    #     """Validate that local contact is a mentor for the course"""
    #     local = self.cleaned_data.get("local")
    #     course = self.cleaned_data.get("course")
    #
    #     if local and course:
    #         # Check if local contact is a mentor for this course
    #         if not course.mentors.filter(id=local.id).exists():
    #             raise forms.ValidationError(
    #                 "The selected local contact is not a mentor for this course."
    #             )
    #
    #     return local
    #
    # def clean(self):
    #     """Additional form validation"""
    #     cleaned_data = super().clean()
    #     trainee = cleaned_data.get("trainee")
    #     examiner = cleaned_data.get("examiner")
    #     local = cleaned_data.get("local")
    #
    #     # Ensure trainee is not the same as examiner or local
    #     if trainee and examiner and trainee == examiner.user:
    #         raise forms.ValidationError("Trainee cannot be the same as the examiner.")
    #
    #     if trainee and local and trainee == local:
    #         raise forms.ValidationError(
    #             "Trainee cannot be the same as the local contact."
    #         )
    #
    #     return cleaned_data


class CPTCreateForm(CPTForm):
    """Form specifically for creating new CPTs with required fields"""

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        # Ensure required fields are marked as such
        self.fields["course"].required = True
        self.fields["trainee"].required = True
        self.fields["date"].required = True


class CPTUpdateForm(CPTForm):
    """Form for updating existing CPTs"""

    class Meta(CPTForm.Meta):
        fields = CPTForm.Meta.fields + ["passed", "confirmed"]

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        # Add passed and confirmed fields for updates
        self.fields["passed"] = forms.ChoiceField(
            choices=[
                ("", "-- Select Status --"),
                (True, "Passed"),
                (False, "Failed"),
            ],
            required=False,
            widget=forms.Select(attrs={"class": "form-control"}),
        )
        self.fields["confirmed"] = forms.BooleanField(
            required=False,
            widget=forms.CheckboxInput(attrs={"class": "form-check-input"}),
        )


class CPTExaminerForm(forms.ModelForm):
    """Form for examiners to update CPT results"""

    class Meta:
        model = CPT
        fields = ["passed", "confirmed"]
        widgets = {
            "passed": forms.Select(
                choices=[
                    ("", "-- Select Result --"),
                    (True, "Passed"),
                    (False, "Failed"),
                ],
                attrs={
                    "class": "form-control",
                    "required": True,
                },
            ),
            "confirmed": forms.CheckboxInput(
                attrs={
                    "class": "form-check-input",
                }
            ),
        }
        labels = {
            "passed": "Result",
            "confirmed": "Confirm Result",
        }

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.fields["passed"].required = True
