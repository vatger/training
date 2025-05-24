#!/bin/sh
set -e

# Collect static files
cd /opt/training/training
python manage.py collectstatic --noinput

# Start nginx
nginx -g "daemon on;"

# Start cron
crontab /opt/training/cron_schedule && crond

# Run Gunicorn
cd /opt/training/training
gunicorn training.wsgi:application --bind 127.0.0.1:8017
