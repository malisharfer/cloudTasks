import os
from dotenv import load_dotenv

load_dotenv()

workspace_id = os.getenv("WORKSPACE_ID")
time_period_for_check_last_fetch=os.getenv("DESIRED_TIME_PERIOD_SINCE_LAST_RETRIEVAL_FOR_CHECK_LAST_FETCH")
time_index_for_check_last_fetch=os.getenv("TIME_INDEX_FOR_CHECK_LAST_FETCH")
