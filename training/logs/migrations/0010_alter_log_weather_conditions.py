# Generated by Django 5.2.1 on 2025-07-09 18:53

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ("logs", "0009_log_traffic_complexity_log_traffic_level_and_more"),
    ]

    operations = [
        migrations.AlterField(
            model_name="log",
            name="weather_conditions",
            field=models.CharField(
                blank=True,
                choices=[
                    ("CAVOK", "CAVOK"),
                    ("VMC", "VMC"),
                    ("IMC", "IMC"),
                    ("WINDY", "Strong Wind"),
                    ("MARG", "Marginal"),
                ],
                max_length=5,
                null=True,
            ),
        ),
    ]
