# middleware.py
from django.utils.deprecation import MiddlewareMixin
from .models import User

class PHPSessionMiddleware(MiddlewareMixin):
    def process_request(self, request):
        session_id = request.COOKIES.get('PHPSESSID')
        if session_id:
            try:
                user = User.objects.get(session_id=session_id)
                request.user = user
                request.is_authenticated = True
            except User.DoesNotExist:
                request.is_authenticated = False
        else:
            request.is_authenticated = False