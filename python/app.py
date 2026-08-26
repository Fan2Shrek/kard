from flask import Flask, jsonify, request

from strategies import crazy_eights, menteur, president

app = Flask(__name__)

# one strategy module per game mode - keys match App\Game\Mode\GameModeEnum
STRATEGIES = {
    "crazy_eights": crazy_eights.move,
    "president": president.move,
    "menteur": menteur.move,
}


@app.route("/move/<game_mode>", methods=["POST"])
def move(game_mode):
    strategy = STRATEGIES.get(game_mode)

    if strategy is None:
        return jsonify({"error": f"unknown game mode '{game_mode}'"}), 404

    cards, data = strategy(request.get_json(silent=True) or {})

    return jsonify({"cards": cards, "data": data})


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000)
