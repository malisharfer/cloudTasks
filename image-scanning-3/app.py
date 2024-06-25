from flask import Flask, request
from waitress import serve
from project.image_scanning import run_resource_graph_query ,send_to_queue

app = Flask(__name__)

@app.route("/try_get")
def try_get():
    send_to_queue()
    return "good!!!"

@app.route("/image_push_acr", methods=["POST"])
def send_to_image_scanning():
    response = request.get_json()
    run_resource_graph_query(response["rg_name"], response["digest"], response["date"])
    return response


if __name__ == "__main__":
    serve(app, host="0.0.0.0", port=8080)
