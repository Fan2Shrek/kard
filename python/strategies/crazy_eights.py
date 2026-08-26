from ._common import card_of, hand_cards, played_turns, turns

# ponytail: joue la premiere carte legale, sinon pioche (liste vide).

WILD = ("8", "joker")


def last_data(payload):
    all_turns = turns(payload)

    return (all_turns[-1].get("data") or {}) if all_turns else {}


def active_state(payload):
    """The suit/rank actually in play, and any stacked draw penalty."""
    data = last_data(payload)
    plays = played_turns(payload)
    top = card_of(payload, plays[-1]["cardIds"][-1]) if plays else {}

    # a wild card overrides the suit of the card physically on top
    return top, data.get("suit") or top.get("suit"), data.get("drawPenalty") or 0


def move(payload):
    top, active_suit, penalty = active_state(payload)
    cards = hand_cards(payload)

    if penalty > 0:
        # must counter with a 2 or eat the pile
        twos = [c for c in cards if c.get("rank") == "2"]

        return ([twos[0]["id"]], {}) if twos else ([], {})

    for card in cards:
        if card.get("rank") in WILD:
            continue

        if card.get("rank") == top.get("rank") or card.get("suit") == active_suit:
            return [card["id"]], {}

    # no plain match: burn a wild card and name the suit we hold the most of
    for card in cards:
        if card.get("rank") in WILD:
            return [card["id"]], {"suit": best_suit(cards, card["id"])}

    return [], {}


def best_suit(cards, excluded_id):
    counts = {}

    for card in cards:
        if card["id"] == excluded_id or not card.get("suit"):
            continue

        counts[card["suit"]] = counts.get(card["suit"], 0) + 1

    return max(counts, key=counts.get) if counts else "h"
