from flask import Flask, request, jsonify

app = Flask(__name__)

@app.route("/move", methods=["POST"])
def move():
    return jsonify({"move": 'pass'})

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000)
