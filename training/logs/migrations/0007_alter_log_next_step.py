# Generated by Django 5.2 on 2025-06-19 19:49

from django.db import migrations, models


class Migration(migrations.Migration):

    dependencies = [
        ("logs", "0006_alter_log_position"),
    ]

    operations = [
        migrations.AlterField(
            model_name="log",
            name="next_step",
            field=models.TextField(blank=True, null=True),
        ),
    ]
