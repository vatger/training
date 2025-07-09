from django.contrib.auth.models import User
from django.db import models

from lists.models import Course


class Log(models.Model):
    class Type(models.TextChoices):
        ONLINE = "O", "Online"
        SIM = "S", "Sim"
        LESSON = "L", "Lesson"

    class Rating(models.IntegerChoices):
        ZERO = 0, "Not rated"
        ONE = 1, "Requirements not met"
        TWO = 2, "Requirements partially met"
        THREE = 3, "Requirements met"
        FOUR = 4, "Requirements exceeded"

    class TrafficLevel(models.TextChoices):
        VERY_LOW = "VL", "Very Low"
        LOW = "L", "Low"
        MEDIUM = "M", "Medium"
        HIGH = "H", "High"
        VERY_HIGH = "VH", "Very High"

    class TrafficComplexity(models.TextChoices):
        SIMPLE = "S", "Simple"
        MODERATE = "M", "Moderate"
        COMPLEX = "C", "Complex"
        VERY_COMPLEX = "VC", "Very Complex"

    class WeatherConditions(models.TextChoices):
        CAVOK = "CAVOK", "CAVOK"
        VMC = "VMC", "VMC"
        IMC = "IMC", "IMC"
        WINDY = "WIND", "Strong Wind"
        MARGINAL = "MARG", "Marginal"

    trainee = models.ForeignKey(User, on_delete=models.CASCADE, related_name="trainee")
    mentor = models.ForeignKey(
        User, on_delete=models.SET_NULL, null=True, related_name="mentor"
    )
    session_date = models.DateField()
    course = models.ForeignKey(Course, on_delete=models.SET_NULL, null=True)
    position = models.CharField(max_length=25)
    type = models.TextField(max_length=1, choices=Type.choices)

    traffic_level = models.CharField(
        max_length=2, choices=TrafficLevel.choices, blank=True, null=True
    )
    traffic_complexity = models.CharField(
        max_length=2, choices=TrafficComplexity.choices, blank=True, null=True
    )
    weather_conditions = models.CharField(
        max_length=5, choices=WeatherConditions.choices, blank=True, null=True
    )
    runway_configuration = models.CharField(
        max_length=50, blank=True, null=True, help_text="e.g. 25L/25R, RNP X, etc."
    )
    surrounding_stations = models.TextField(
        blank=True,
        null=True,
        help_text="List of active surrounding positions during the session",
    )
    session_duration = models.PositiveIntegerField(
        blank=True, null=True, help_text="Session duration in minutes"
    )
    special_procedures = models.TextField(
        blank=True,
        null=True,
        help_text="Any special procedures, events, or circumstances during the session",
    )
    airspace_restrictions = models.TextField(
        blank=True,
        null=True,
        help_text="Any active NOTAMs, restrictions, or special airspace configurations",
    )

    # Rating categories with positives and negatives
    theory = models.IntegerField(choices=Rating.choices)
    theory_positives = models.TextField(blank=True, null=True)
    theory_negatives = models.TextField(blank=True, null=True)

    phraseology = models.IntegerField(choices=Rating.choices)
    phraseology_positives = models.TextField(blank=True, null=True)
    phraseology_negatives = models.TextField(blank=True, null=True)

    coordination = models.IntegerField(choices=Rating.choices)
    coordination_positives = models.TextField(blank=True, null=True)
    coordination_negatives = models.TextField(blank=True, null=True)

    tag_management = models.IntegerField(choices=Rating.choices)
    tag_management_positives = models.TextField(blank=True, null=True)
    tag_management_negatives = models.TextField(blank=True, null=True)

    situational_awareness = models.IntegerField(choices=Rating.choices)
    situational_awareness_positives = models.TextField(blank=True, null=True)
    situational_awareness_negatives = models.TextField(blank=True, null=True)

    problem_recognition = models.IntegerField(choices=Rating.choices)
    problem_recognition_positives = models.TextField(blank=True, null=True)
    problem_recognition_negatives = models.TextField(blank=True, null=True)

    traffic_planning = models.IntegerField(choices=Rating.choices)
    traffic_planning_positives = models.TextField(blank=True, null=True)
    traffic_planning_negatives = models.TextField(blank=True, null=True)

    reaction = models.IntegerField(choices=Rating.choices)
    reaction_positives = models.TextField(blank=True, null=True)
    reaction_negatives = models.TextField(blank=True, null=True)

    separation = models.IntegerField(choices=Rating.choices)
    separation_positives = models.TextField(blank=True, null=True)
    separation_negatives = models.TextField(blank=True, null=True)

    efficiency = models.IntegerField(choices=Rating.choices)
    efficiency_positives = models.TextField(blank=True, null=True)
    efficiency_negatives = models.TextField(blank=True, null=True)

    ability_to_work_under_pressure = models.IntegerField(choices=Rating.choices)
    ability_to_work_under_pressure_positives = models.TextField(blank=True, null=True)
    ability_to_work_under_pressure_negatives = models.TextField(blank=True, null=True)

    motivation = models.IntegerField(choices=Rating.choices)
    motivation_positives = models.TextField(blank=True, null=True)
    motivation_negatives = models.TextField(blank=True, null=True)

    internal_remarks = models.TextField(blank=True, null=True)
    final_comment = models.TextField(blank=True, null=True)
    result = models.BooleanField()

    next_step = models.TextField(blank=True, null=True)

    def __str__(self):
        return f"Training Log - {self.session_date.strftime('%Y-%m-%d')} - {self.trainee.username} ({self.position})"
