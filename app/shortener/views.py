# views.py
from django.shortcuts import render, redirect
from django.http import JsonResponse
from .models import User, ShortenedURL
from django.utils import timezone
import random
import string

def home(request):
    if not request.is_authenticated:
        return redirect('http://192.168.1.19:8080')  # Redirect to your PHP login page
    
    user = request.user
    urls = ShortenedURL.objects.filter(user=user).order_by('-created_at')
    
    context = {
        'username': user.username,
        'urls': urls,
        'total_urls': urls.count(),
    }
    return render(request, 'home.html', context)

def shorten_url(request):
    if not request.is_authenticated:
        return JsonResponse({'error': 'Authentication required'}, status=401)
    
    if request.method == 'POST':
        original_url = request.POST.get('original_url')
        if not original_url:
            return JsonResponse({'error': 'URL is required'}, status=400)
	
        shortened_url = ShortenedURL.objects.create(
            original_url=original_url,
            user=request.user
        )
        
        return JsonResponse({
            'original_url': shortened_url.original_url,
            'short_url': f"http://192.168.1.19:8081/{shortened_url.short_code}",
            'timestamp': shortened_url.created_at.strftime('%d/%m/%Y %H:%M:%S')
        })
    
    return JsonResponse({'error': 'Method not allowed'}, status=405)

def delete_url(request, url_id):
    if not request.is_authenticated:
        return JsonResponse({'error': 'Authentication required'}, status=401)
    
    try:
        url = ShortenedURL.objects.get(id=url_id, user=request.user)
        url.delete()
        return JsonResponse({'success': True})
    except ShortenedURL.DoesNotExist:
        return JsonResponse({'error': 'URL not found'}, status=404)

def redirect_to_original(request, short_code):
    try:
        url = ShortenedURL.objects.get(short_code=short_code)
        return redirect(url.original_url)
    except ShortenedURL.DoesNotExist:
        return render(request, '404.html', status=404)
