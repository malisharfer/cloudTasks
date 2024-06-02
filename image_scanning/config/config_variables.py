import os
from dotenv import load_dotenv

load_dotenv()

queue_name = os.getenv("QUEUE_NAME")
connection_string = os.getenv("CONNECTION_STRING")
