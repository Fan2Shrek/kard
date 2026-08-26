"""Run: python3 test_strategies.py"""

from strategies import crazy_eights, menteur, president


def payload(hand, turns=(), extra_cards=()):
    cards = {c["id"]: c for c in list(hand) + list(extra_cards)}

    return {
        "hand": [c["id"] for c in hand],
        "cards": cards,
        "players": [],
        "round": {"isNew": not turns, "turns": list(turns)},
    }


def card(id, rank, suit="h"):
    return {"id": id, "rank": rank, "suit": suit}


def turn(card_ids, data=None):
    return {"playerId": "x", "cardIds": list(card_ids), "data": data or {}}


# ---- president -------------------------------------------------------------

# opening: weakest single, and never a 2 (it would end the round immediately)
cards, _ = president.move(payload([card("a", "2"), card("b", "9"), card("c", "5")]))
assert cards == ["c"], cards

# must match the trick size: a single can't answer a pair
top = [card("t1", "7", "h"), card("t2", "7", "s")]
cards, _ = president.move(
    payload([card("a", "9", "h"), card("b", "9", "s"), card("c", "k")],
            turns=[turn(["t1", "t2"])], extra_cards=top)
)
assert cards == ["a", "b"], cards

# only a single high card, but the trick is a pair -> pass
cards, _ = president.move(
    payload([card("c", "k")], turns=[turn(["t1", "t2"])], extra_cards=top)
)
assert cards == [], cards

# equal rank is legal, and it's the weakest option
cards, _ = president.move(
    payload([card("a", "7", "d"), card("b", "k")],
            turns=[turn(["t1"])], extra_cards=[card("t1", "7", "h")])
)
assert cards == ["a"], cards

# nothing high enough -> pass
cards, _ = president.move(
    payload([card("a", "4")], turns=[turn(["t1"])], extra_cards=[card("t1", "k")])
)
assert cards == [], cards

# "carte ou rien": two same-rank plays in a row lock the round on that rank
locked = payload(
    [card("a", "9", "d"), card("b", "k")],
    turns=[turn(["t1"]), turn(["t2"])],
    extra_cards=[card("t1", "9", "h"), card("t2", "9", "s")],
)
cards, _ = president.move(locked)
assert cards == ["a"], cards

# ... and we pass rather than play off-rank while it's locked
locked["hand"] = ["b"]
assert president.move(locked) == ([], {})

# a pass in the middle doesn't become the trick to beat
cards, _ = president.move(
    payload([card("a", "9")], turns=[turn(["t1"]), turn([])],
            extra_cards=[card("t1", "5")])
)
assert cards == ["a"], cards

# ---- crazy eights ----------------------------------------------------------

# matches the suit, and keeps the 8 in reserve
cards, data = crazy_eights.move(
    payload([card("a", "8", "s"), card("b", "5", "h")],
            turns=[turn(["t"])], extra_cards=[card("t", "9", "h")])
)
assert (cards, data) == (["b"], {}), (cards, data)

# no match -> burn the 8 and name the suit we hold most of
cards, data = crazy_eights.move(
    payload([card("a", "8", "s"), card("b", "5", "c"), card("c", "7", "c")],
            turns=[turn(["t"])], extra_cards=[card("t", "9", "h")])
)
assert cards == ["a"] and data == {"suit": "c"}, (cards, data)

# a suit declared by a previous wild card beats the top card's own suit
cards, _ = crazy_eights.move(
    payload([card("b", "5", "c")], turns=[turn(["t"], {"suit": "c"})],
            extra_cards=[card("t", "8", "h")])
)
assert cards == ["b"], cards

# pending penalty: only a 2 counters
pen = [turn(["t"], {"drawPenalty": 4})]
assert crazy_eights.move(payload([card("b", "5", "c")], turns=pen,
                                 extra_cards=[card("t", "2", "h")])) == ([], {})
assert crazy_eights.move(payload([card("b", "2", "c")], turns=pen,
                                 extra_cards=[card("t", "2", "h")])) == (["b"], {})

# empty hand, or nothing playable -> draw
assert crazy_eights.move(payload([])) == ([], {})

# ---- menteur ---------------------------------------------------------------

# new round starts the cycle at 3, and we tell the truth when we can
cards, data = menteur.move(payload([card("a", "9"), card("b", "3")]))
assert (cards, data) == (["b"], {"rank": "3"}), (cards, data)

# mid-round the rank follows the cycle, and we bluff when we lack it
cards, data = menteur.move(
    payload([card("a", "9")], turns=[turn(["t"], {"rank": "k"})],
            extra_cards=[card("t", "k")])
)
assert (cards, data) == (["a"], {"rank": "1"}), (cards, data)

# the cycle wraps past 2 back to 3
_, data = menteur.move(
    payload([card("a", "9")], turns=[turn(["t"], {"rank": "2"})],
            extra_cards=[card("t", "2")])
)
assert data == {"rank": "3"}, data

assert menteur.move(payload([])) == ([], {})

print("ok")
