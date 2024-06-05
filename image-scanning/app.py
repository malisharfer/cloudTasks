from flask import Flask ,request
from waitress import serve
app = Flask(__name__)
from project.image_scanning import run_resource_graph_query

@app.route("/image_push_acr", methods=["POST"])
def add_new_user():
    respons = request.get_json()
    run_resource_graph_query(respons["rg_name"],respons["digest"],respons["date"])
    return respons

if __name__ == "__main__":
    serve(app, host="0.0.0.0", port=8080)
