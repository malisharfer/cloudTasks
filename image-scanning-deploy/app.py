from flask import Flask, request,jsonify
from waitress import serve
from project.image_scanning import run_resource_graph_query ,send_to_queue

app = Flask(__name__)


@app.route("/image_push_acr", methods=["POST"])
def send_to_image_scanning():
    # data = request.json 
    # print("Received webhook data:", data)
    # send_to_queue(data)
    return jsonify({'message': 'Webhook received successfully'}), 200
    # response = request.get_json()
    # # run_resource_graph_query(response["rg_name"], response["digest"], response["date"])
    # send_to_queue("yyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyyy")
    # return response




if __name__ == "__main__":
    serve(app, host="0.0.0.0", port=8080)
