"""Run: python3 test_strategies.py"""

from strategies import crazy_eights, menteur, president
from strategies._common import hand_cards


def payload(hand, turns=()):
    """Shaped like GameStateDTO: the opponent's hand is redacted, ours isn't."""
    return {
        "players": [
            {"id": "me", "username": "bot", "score": 0,
             "cardsCount": len(hand), "hand": list(hand)},
            {"id": "x", "username": "them", "score": 0, "cardsCount": 5, "hand": None},
        ],
        "playerOrder": ["me", "x"],
        "currentPlayerId": "me",
        "rounds": [list(turns)],
        "discardPile": [],
        "drawPileCount": 10,
        "everyoneCanPlay": False,
    }


def card(id, rank, suit="h"):
    return {"id": id, "rank": rank, "suit": suit}


def turn(cards, data=None):
    return {"playerId": "x", "cards": list(cards), "data": data or {}}


# a redacted hand is never mistaken for ours
assert hand_cards(payload([card("a", "9")])) == [card("a", "9")]


# ---- president -------------------------------------------------------------

# opening: weakest single, and never a 2 (it would end the round immediately)
cards, _ = president.move(payload([card("a", "2"), card("b", "9"), card("c", "5")]))
assert cards == ["c"], cards

# must match the trick size: a single can't answer a pair
pair = turn([card("t1", "7", "h"), card("t2", "7", "s")])
cards, _ = president.move(
    payload([card("a", "9", "h"), card("b", "9", "s"), card("c", "k")], turns=[pair])
)
assert cards == ["a", "b"], cards

# only a single high card, but the trick is a pair -> pass
cards, _ = president.move(payload([card("c", "k")], turns=[pair]))
assert cards == [], cards

# equal rank is legal, and it's the weakest option
cards, _ = president.move(
    payload([card("a", "7", "d"), card("b", "k")],
            turns=[turn([card("t1", "7", "h")])])
)
assert cards == ["a"], cards

# nothing high enough -> pass
cards, _ = president.move(
    payload([card("a", "4")], turns=[turn([card("t1", "k")])])
)
assert cards == [], cards

# "carte ou rien": two same-rank plays in a row lock the round on that rank
lock = [turn([card("t1", "9", "h")]), turn([card("t2", "9", "s")])]
cards, _ = president.move(payload([card("a", "9", "d"), card("b", "k")], turns=lock))
assert cards == ["a"], cards

# ... and we pass rather than play off-rank while it's locked
assert president.move(payload([card("b", "k")], turns=lock)) == ([], {})

# a pass in the middle doesn't become the trick to beat
cards, _ = president.move(
    payload([card("a", "9")], turns=[turn([card("t1", "5")]), turn([])])
)
assert cards == ["a"], cards

# ---- crazy eights ----------------------------------------------------------

nine_h = [turn([card("t", "9", "h")])]

# matches the suit, and keeps the 8 in reserve
cards, data = crazy_eights.move(
    payload([card("a", "8", "s"), card("b", "5", "h")], turns=nine_h)
)
assert (cards, data) == (["b"], {}), (cards, data)

# no match -> burn the 8 and name the suit we hold most of
cards, data = crazy_eights.move(
    payload([card("a", "8", "s"), card("b", "5", "c"), card("c", "7", "c")], turns=nine_h)
)
assert cards == ["a"] and data == {"suit": "c"}, (cards, data)

# a suit declared by a previous wild card beats the top card's own suit
cards, _ = crazy_eights.move(
    payload([card("b", "5", "c")], turns=[turn([card("t", "8", "h")], {"suit": "c"})])
)
assert cards == ["b"], cards

# pending penalty: only a 2 counters
pen = [turn([card("t", "2", "h")], {"drawPenalty": 4})]
assert crazy_eights.move(payload([card("b", "5", "c")], turns=pen)) == ([], {})
assert crazy_eights.move(payload([card("b", "2", "c")], turns=pen)) == (["b"], {})

# empty hand, or nothing playable -> draw
assert crazy_eights.move(payload([])) == ([], {})

# ---- menteur ---------------------------------------------------------------

# new round starts the cycle at 3, and we tell the truth when we can
cards, data = menteur.move(payload([card("a", "9"), card("b", "3")]))
assert (cards, data) == (["b"], {"rank": "3"}), (cards, data)

# mid-round the rank follows the cycle, and we bluff when we lack it
cards, data = menteur.move(
    payload([card("a", "9")], turns=[turn([card("t", "k")], {"rank": "k"})])
)
assert (cards, data) == (["a"], {"rank": "1"}), (cards, data)

# the cycle wraps past 2 back to 3
_, data = menteur.move(
    payload([card("a", "9")], turns=[turn([card("t", "2")], {"rank": "2"})])
)
assert data == {"rank": "3"}, data

assert menteur.move(payload([])) == ([], {})

print("ok")
