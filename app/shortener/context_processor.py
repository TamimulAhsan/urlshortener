# context_processors.py
from django.conf import settings

def php_logout_url(request):
    return {'php_logout_url': settings.PHP_LOGOUT_URL}