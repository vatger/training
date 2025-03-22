from django import forms
from .models import Comment


class CommentForm(forms.ModelForm):
    class Meta:
        model = Comment
        exclude = ["user", "date_added", "author"]
        widgets = {
            "text": forms.Textarea(attrs={"rows": 3}),
        }


class UserDetailForm(forms.Form):
    user_id = forms.CharField(
        max_length=7,
        label="User ID",
        widget=forms.TextInput(
            attrs={"class": "form-control", "placeholder": "Enter VATSIM ID"}
        ),
    )
