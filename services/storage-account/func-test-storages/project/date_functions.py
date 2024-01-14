import datetime


def get_date(time):
    seconds = return_seconds(time)
    required_date = datetime.date.today() - datetime.timedelta(seconds=seconds)
    return required_date


def return_seconds(obj_time):
    match obj_time["type_of_time"]:
        case "days":
            seconds = obj_time["number"] * 24 * 60 * 60
        case "weeks":
            seconds = obj_time["number"] * 24 * 60 * 60 * 7
        case "months":
            seconds = obj_time["number"] * 24 * 60 * 60 * 30
        case "years":
            seconds = obj_time["number"] * 24 * 60 * 60 * 365
        case _:
            seconds = -1
    return seconds


def calculate_the_dif_between_two_dates(date1, date2):
    return (date1 - date2).days


def convert_to_date_type(azure_date):
    return datetime.datetime.strptime(azure_date, "%Y-%m-%d %H:%M:%S%f%z").date()


def convert_to_date_type_mil_seconds(azure_date):
    return datetime.datetime.strptime(azure_date, "%Y-%m-%d %H:%M:%S.%f%z").date()

def convert_datetime_to_date(date):
    return datetime.date(date.year,date.month, date.day)
