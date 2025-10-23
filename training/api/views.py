# views.py
from django.http import JsonResponse, HttpResponse
from django.contrib.auth.models import User
from django.views.decorators.csrf import csrf_exempt
from dotenv import load_dotenv
import os
import requests

from training.eud_header import eud_header
from familiarisations.models import Familiarisation
from endorsements.helpers import remove_roster_and_endorsements

load_dotenv()


@csrf_exempt
def user_delete_view(request, vatsim_id):
    # Check for DELETE method
    if request.method == "DELETE":
        # Verify Authorization Token
        auth_header = request.headers.get("Authorization")
        if auth_header == f"Token {os.getenv('GDPR_KEY')}":
            try:
                user = User.objects.get(username=vatsim_id)
                remove_roster_and_endorsements(int(vatsim_id))
                requests.delete(
                    f"https://core.vateud.net/api/facility/visitors/{int(vatsim_id)}/delete",
                    headers=eud_header,
                )
                user.delete()
                return JsonResponse(
                    {"message": "User deleted successfully"}, status=200
                )
            except User.DoesNotExist:
                return JsonResponse({"error": "User not found"}, status=200)
        else:
            return JsonResponse({"error": "Unauthorized"}, status=401)
    else:
        return HttpResponse(status=405)  # Method Not Allowed


@csrf_exempt
def user_retrieve_view(request, vatsim_id):
    # Check for GET method
    if request.method == "GET":
        # Verify Authorization Token
        auth_header = request.headers.get("Authorization")
        if auth_header == f"Token {os.getenv('GDPR_KEY')}":
            try:
                user = User.objects.get(username=vatsim_id)
                user_data = {}
                for field in user._meta.get_fields():
                    if not field.is_relation:  # Exclude related fields
                        user_data[field.name] = getattr(user, field.name)
                return JsonResponse(user_data, status=200)
            except User.DoesNotExist:
                return JsonResponse({"error": "User not found"}, status=200)
        else:
            return JsonResponse({"error": "Unauthorized"}, status=401)
    else:
        return HttpResponse(status=405)  # Method Not Allowed


@csrf_exempt
def tier1_endorsements(request):
    if request.method == "GET":
        auth_header = request.headers.get("Authorization")
        if auth_header == f"Token {os.getenv('INT_API_KEY')}":
            response = requests.get(
                "https://core.vateud.net/api/facility/endorsements/tier-1",
                headers=eud_header,
                timeout=10,
            ).json()
            return JsonResponse(response, status=200)
        else:
            return JsonResponse({"error": "Unauthorized"}, status=401)
    else:
        return HttpResponse(status=405)


@csrf_exempt
def familiarisations(request):
    if request.method == "GET":
        auth_header = request.headers.get("Authorization")
        if auth_header == f"Token {os.getenv('INT_API_KEY')}":
            data = list(Familiarisation.objects.values(
                'user__username',
                'sector__name'
            ))
            return JsonResponse(data, safe=False, status=200)
        else:
            return JsonResponse({"error": "Unauthorized"}, status=401)
    else:
        return HttpResponse(status=405)
