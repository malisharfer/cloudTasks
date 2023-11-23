import azure.functions as func
import logging
import datetime

app = func.FunctionApp()

@app.function_name(name="mytimer")
@app.schedule(schedule="* * * * * *", arg_name="mytimer", run_on_startup=False,
              use_monitor=False) 
def test_function(mytimer: func.TimerRequest) -> None:
    utc_timestamp = datetime.datetime.utcnow().replace(
        tzinfo=datetime.timezone.utc).isoformat()

    if mytimer.past_due:
        logging.info('The timer is past due!')


    logging.info('Python timer trigger function ran at %s', utc_timestamp)
