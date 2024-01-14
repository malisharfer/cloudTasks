import azure.functions as func
from read_log_analytics import *


app = func.FunctionApp()

@app.function_name(name="HttpTrigger1")
@app.route(route="")
def test_function(req: func.HttpRequest) -> func.HttpResponse:

    max_time_foreach_storage = get_array_of_last_fetch_time()

    return func.HttpResponse(str(max_time_foreach_storage),status_code=200)
