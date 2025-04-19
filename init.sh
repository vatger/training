nginx -g "daemon on;"

crontab cron_schedule && crond
cd training && python3 manage.py collectstatic --noinput
cd training && gunicorn training.wsgi --bind 127.0.0.1:8017