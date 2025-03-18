# urls.py
from django.urls import path
from . import views

urlpatterns = [
    path('', views.home, name='home'),
    path('shorten/', views.shorten_url, name='shorten_url'),
    path('delete/<int:url_id>/', views.delete_url, name='delete_url'),
    path('<str:short_code>/', views.redirect_to_original, name='redirect_to_original'),
]
