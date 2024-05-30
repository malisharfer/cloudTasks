from flask import Flask
app = Flask(__name__)
from waitress import serve

@app.route('/')
def main():
    return 'Hello, World!'

if __name__ == "__main__":
    serve(app, host="0.0.0.0", port=8080)


# from flask import Flask
# app = Flask(__name__)

# @app.route('/')
# def hello_geek():
#     return '<h1>Hello from Flask & Docker</h2>'


# if __name__ == "__main__":
#     app.run(debug=True)