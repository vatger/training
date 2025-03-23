from django import forms
from .models import Log


class TrainingLogForm(forms.ModelForm):
    class Meta:
        model = Log
        exclude = ["trainee", "mentor", "course"]
        widgets = {
            "theory_positives": forms.Textarea(attrs={"rows": 3}),
            "theory_negatives": forms.Textarea(attrs={"rows": 3}),
            "phraseology_positives": forms.Textarea(attrs={"rows": 3}),
            "phraseology_negatives": forms.Textarea(attrs={"rows": 3}),
            "coordination_positives": forms.Textarea(attrs={"rows": 3}),
            "coordination_negatives": forms.Textarea(attrs={"rows": 3}),
            "tag_management_positives": forms.Textarea(attrs={"rows": 3}),
            "tag_management_negatives": forms.Textarea(attrs={"rows": 3}),
            "situational_awareness_positives": forms.Textarea(attrs={"rows": 3}),
            "situational_awareness_negatives": forms.Textarea(attrs={"rows": 3}),
            "problem_recognition_positives": forms.Textarea(attrs={"rows": 3}),
            "problem_recognition_negatives": forms.Textarea(attrs={"rows": 3}),
            "traffic_planning_positives": forms.Textarea(attrs={"rows": 3}),
            "traffic_planning_negatives": forms.Textarea(attrs={"rows": 3}),
            "reaction_positives": forms.Textarea(attrs={"rows": 3}),
            "reaction_negatives": forms.Textarea(attrs={"rows": 3}),
            "separation_positives": forms.Textarea(attrs={"rows": 3}),
            "separation_negatives": forms.Textarea(attrs={"rows": 3}),
            "efficiency_positives": forms.Textarea(attrs={"rows": 3}),
            "efficiency_negatives": forms.Textarea(attrs={"rows": 3}),
            "ability_to_work_under_pressure_positives": forms.Textarea(
                attrs={"rows": 3}
            ),
            "ability_to_work_under_pressure_negatives": forms.Textarea(
                attrs={"rows": 3}
            ),
            "motivation_positives": forms.Textarea(attrs={"rows": 3}),
            "motivation_negatives": forms.Textarea(attrs={"rows": 3}),
            "internal_remarks": forms.Textarea(attrs={"rows": 4}),
            "final_comment": forms.Textarea(attrs={"rows": 4}),
            "session_date": forms.widgets.DateInput(
                attrs={"type": "date"}, format="%Y-%m-%d"
            ),
            "next_step": forms.widgets.TextInput(),
        }
