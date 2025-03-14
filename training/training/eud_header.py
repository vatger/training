import os

from dotenv import load_dotenv


load_dotenv()

eud_header = {
    "X-API-KEY": os.getenv("CORE_API"),
    "Accept": "application/json",
    "User-Agent": "VATGER",
}
