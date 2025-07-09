from django import forms

from .models import Log


class TrainingLogForm(forms.ModelForm):
    class Meta:
        model = Log
        exclude = ["trainee", "mentor", "course"]
        widgets = {
            # Session details
            "session_date": forms.widgets.DateInput(
                attrs={"type": "date"}, format="%Y-%m-%d"
            ),
            "position": forms.TextInput(attrs={"placeholder": "e.g. EDDF_APP"}),
            "type": forms.Select(),
            # Additional session details
            "traffic_level": forms.Select(),
            "traffic_complexity": forms.Select(),
            "weather_conditions": forms.Select(),
            "runway_configuration": forms.TextInput(
                attrs={"placeholder": "e.g. 25L/07R, ILS 25L"}
            ),
            "surrounding_stations": forms.Textarea(
                attrs={"rows": 2, "placeholder": "e.g. EDDF_TWR, EDDF_GND, EDDF_N_APP"}
            ),
            "session_duration": forms.NumberInput(
                attrs={"placeholder": "Duration in minutes"}
            ),
            "special_procedures": forms.Textarea(
                attrs={"rows": 2, "placeholder": "Any special procedures or events"}
            ),
            "airspace_restrictions": forms.Textarea(
                attrs={"rows": 2, "placeholder": "NOTAMs, restrictions, etc."}
            ),
            # Basic evaluation fields
            "theory": forms.Select(),
            "theory_positives": forms.Textarea(attrs={"rows": 3}),
            "theory_negatives": forms.Textarea(attrs={"rows": 3}),
            # Phraseology
            "phraseology": forms.Select(),
            "phraseology_positives": forms.Textarea(attrs={"rows": 3}),
            "phraseology_negatives": forms.Textarea(attrs={"rows": 3}),
            # Coordination
            "coordination": forms.Select(),
            "coordination_positives": forms.Textarea(attrs={"rows": 3}),
            "coordination_negatives": forms.Textarea(attrs={"rows": 3}),
            # Tag Management
            "tag_management": forms.Select(),
            "tag_management_positives": forms.Textarea(attrs={"rows": 3}),
            "tag_management_negatives": forms.Textarea(attrs={"rows": 3}),
            # Situational Awareness
            "situational_awareness": forms.Select(),
            "situational_awareness_positives": forms.Textarea(attrs={"rows": 3}),
            "situational_awareness_negatives": forms.Textarea(attrs={"rows": 3}),
            # Problem Recognition
            "problem_recognition": forms.Select(),
            "problem_recognition_positives": forms.Textarea(attrs={"rows": 3}),
            "problem_recognition_negatives": forms.Textarea(attrs={"rows": 3}),
            # Traffic Planning
            "traffic_planning": forms.Select(),
            "traffic_planning_positives": forms.Textarea(attrs={"rows": 3}),
            "traffic_planning_negatives": forms.Textarea(attrs={"rows": 3}),
            # Reaction
            "reaction": forms.Select(),
            "reaction_positives": forms.Textarea(attrs={"rows": 3}),
            "reaction_negatives": forms.Textarea(attrs={"rows": 3}),
            # Separation
            "separation": forms.Select(),
            "separation_positives": forms.Textarea(attrs={"rows": 3}),
            "separation_negatives": forms.Textarea(attrs={"rows": 3}),
            # Efficiency
            "efficiency": forms.Select(),
            "efficiency_positives": forms.Textarea(attrs={"rows": 3}),
            "efficiency_negatives": forms.Textarea(attrs={"rows": 3}),
            # Work Under Pressure
            "ability_to_work_under_pressure": forms.Select(),
            "ability_to_work_under_pressure_positives": forms.Textarea(
                attrs={"rows": 3}
            ),
            "ability_to_work_under_pressure_negatives": forms.Textarea(
                attrs={"rows": 3}
            ),
            # Motivation
            "motivation": forms.Select(),
            "motivation_positives": forms.Textarea(attrs={"rows": 3}),
            "motivation_negatives": forms.Textarea(attrs={"rows": 3}),
            # Final assessment
            "internal_remarks": forms.Textarea(attrs={"rows": 4}),
            "final_comment": forms.Textarea(attrs={"rows": 4}),
            "next_step": forms.widgets.TextInput(),
        }
