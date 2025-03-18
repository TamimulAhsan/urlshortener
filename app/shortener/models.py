# In shortener/models.py
from django.db import models
import random
import string
from django.utils import timezone

class User(models.Model):
    unique_id = models.CharField(max_length=255, primary_key=True)
    username = models.CharField(max_length=255, unique=True)
    hashed_password = models.CharField(max_length=255)
    timestamp = models.DateTimeField()
    session_id = models.CharField(max_length=255, null=True, blank=True)

    class Meta:
        # This tells Django this is an existing table
        db_table = 'users'
        managed = False  # Don't try to create/modify this table

class ShortenedURL(models.Model):
    original_url = models.URLField(max_length=2000)
    short_code = models.CharField(max_length=10, unique=True)
    user = models.ForeignKey(User, on_delete=models.CASCADE)
    created_at = models.DateTimeField(auto_now_add=True)

    def save(self, *args, **kwargs):
        if not self.short_code:
            self.short_code = self.generate_short_code()
        super().save(*args, **kwargs)

    def generate_short_code(self):
        characters = string.ascii_letters + string.digits
        short_code = ''.join(random.choice(characters) for _ in range(6))
        return short_code

    class Meta:
        db_table = 'shortener_shortenedurl'
        managed = False  # Django won't try to create/modify this table