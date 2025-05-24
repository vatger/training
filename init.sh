#!/bin/sh
sleep 1
cd /opt/training/training && python manage.py collectstatic --noinput

nginx -g "daemon on;"
crontab /opt/training/cron_schedule && crond
cd training && gunicorn training.wsgi --bind 127.0.0.1:8017
