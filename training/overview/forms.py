from django import forms
from django.contrib.auth.models import User


class AddUserForm(forms.Form):
    username = forms.CharField(label="ID", max_length=7)
    course_id = forms.IntegerField(widget=forms.HiddenInput())


class SoloForm(forms.Form):
    expiry = forms.DateField(widget=forms.widgets.DateInput(attrs={"type": "date"}))
