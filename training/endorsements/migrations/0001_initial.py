# Generated by Django 5.1.5 on 2025-03-22 18:36

import datetime
import django.db.models.deletion
from django.db import migrations, models


class Migration(migrations.Migration):

    initial = True

    dependencies = []

    operations = [
        migrations.CreateModel(
            name="EndorsementGroup",
            fields=[
                (
                    "id",
                    models.BigAutoField(
                        auto_created=True,
                        primary_key=True,
                        serialize=False,
                        verbose_name="ID",
                    ),
                ),
                ("name", models.CharField(max_length=15)),
            ],
        ),
        migrations.CreateModel(
            name="EndorsementActivity",
            fields=[
                ("id", models.IntegerField(primary_key=True, serialize=False)),
                ("activity", models.FloatField(default=0.0)),
                (
                    "updated",
                    models.DateTimeField(default=datetime.datetime(2000, 1, 1, 0, 0)),
                ),
                ("removal_date", models.DateField(blank=True, null=True)),
                ("removal_notified", models.BooleanField(default=False)),
                ("created", models.DateTimeField(default=datetime.datetime.now)),
                (
                    "group",
                    models.ForeignKey(
                        on_delete=django.db.models.deletion.CASCADE,
                        to="endorsements.endorsementgroup",
                    ),
                ),
            ],
        ),
    ]
