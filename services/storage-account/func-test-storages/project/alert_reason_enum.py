from enum import Enum
from project.config_variables import (time_period_for_check_last_fetch as time_fetch,
                            time_index_for_check_last_fetch as type_time_fetch,
                            time_period_for_check_used_capacity as time_capacity,
                            time_index_for_check_used_capacity as type_time_capacity)

class alert_reasons(Enum):
    USED_CAPACITY = f"The amount of storage in use has increased in the last {time_capacity} {type_time_capacity}"
    LAST_FETCH_TIME = f"The Storge has not been used for {time_fetch} {type_time_fetch}"
