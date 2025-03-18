#!/bin/bash

# Path to your virtual environment's activate script
VENV_PATH="/your-venv-path/bin/activate"

# Path to your Django project's manage.py
MANAGE_PY_PATH="/your-project-path/app/manage.py"

# Path to your Gunicorn or Daphne executable (if using)
GUNICORN_PATH="/your-venv-path/bin/gunicorn" # or daphne

# Path to your log file
ACCESS_LOG="/var/log/urlshortener/access.log"
ERROR_LOG="/var/log/urlshortener/error.log"

# Django settings module
#DJANGO_SETTINGS_MODULE="urlshortener.settings"

# Application name
APPLICATION_NAME="urlshortener.wsgi" 

# Activate the virtual environment
source "$VENV_PATH"

# Run the Django development server (for testing) or Gunicorn/Daphne (for production)
# Development server (not recommended for production):
# python "$MANAGE_PY_PATH" runserver 0.0.0.0:8001 >> "$LOG_FILE" 2>&1 &

"$GUNICORN_PATH" "$APPLICATION_NAME" \
    --bind 0.0.0.0:8081 \
    --access-logfile "$ACCESS_LOG" \
    --error-logfile "$ERROR_LOG" &

echo "Django app started in the background at http://0.0.0.0:8081"

# Deactivate the virtual environment (optional)
deactivate
