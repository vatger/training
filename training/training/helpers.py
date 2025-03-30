from django.contrib.admin.models import LogEntry
from django.contrib.contenttypes.models import ContentType


def log_admin_action(user, instance, action_flag, message):
    """Manually creates a LogEntry row."""
    LogEntry.objects.create(
        user=user,
        content_type=ContentType.objects.get_for_model(instance),
        object_id=instance.pk,
        object_repr=str(instance),
        action_flag=action_flag,
        change_message=message,
    )
