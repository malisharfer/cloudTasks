from unittest.mock import patch, Mock
from datetime import date, timedelta
from project.date_functions import *


@patch(
    "project.date_functions.datetime.date",
    Mock(**{"today.return_value": date(2023, 11, 6)}),
)
@patch(
    "project.date_functions.datetime.timedelta",
    Mock(return_value=timedelta(seconds=7776000)),
)
def test_get_date():
    assert get_date({"type_of_time": "years", "number": 1}) == date(2023, 8, 8)


def test_return_seconds():
    assert return_seconds({"type_of_time": "days", "number": 10}) == 864000
    assert return_seconds({"type_of_time": "weeks", "number": 2}) == 1209600
    assert return_seconds({"type_of_time": "months", "number": 3}) == 7776000
    assert return_seconds({"type_of_time": "years", "number": 1}) == 31536000
    assert (
        return_seconds({"type_of_time": "f", "number": 1}) == -1
    ), "type date is not valid"


def test_calculate_the_dif_between_two_dates():
    assert calculate_the_dif_between_two_dates(date(2023, 8, 8), date(2023, 8, 7)) == 1


def test_convert_to_date_type():
    assert convert_to_date_type("2023-07-11 00:00:00+00:00") == date(
        2023, 7, 11
    )

def test_convert_to_date_type_mil_seconds():
    assert convert_to_date_type_mil_seconds("2023-07-11 00:00:00.00+00:00") == date(2023,7,11)

def test_convert_datetime_to_date():
    assert convert_datetime_to_date(datetime.datetime(2023,11,29))== date(2023,11,29)
