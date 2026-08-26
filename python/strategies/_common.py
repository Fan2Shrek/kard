"""Helpers shared by every strategy.

The payload PHP sends (see App\\Service\\Bot\\GameAI::buildPayload):

    {
      "hand": ["<cardId>", ...],
      "cards": {"<cardId>": {"id": ..., "rank": "8", "suit": "h"}, ...},
      "players": [{"id": ..., "cardsCount": 5, "isBot": false}, ...],
      "round": {
        "isNew": true,
        "turns": [{"playerId": ..., "cardIds": [...], "data": {...}}, ...]
      }
    }

The round's turns are raw on purpose: each strategy derives what its own game
mode derives from them, rather than PHP guessing per mode.

A move is (card_ids, data). An empty list means "I play nothing" - the game mode
decides what that means (draw, pass, ...) and may well reject it.
"""

# Rank values as stored by App\Enum\Card\Rank - note ace is "1" and face cards
# are lowercase. Ordered weakest to strongest for trick-taking games.
ORDER = ["3", "4", "5", "6", "7", "8", "9", "10", "j", "q", "k", "1", "2"]


def rank_value(rank):
    return ORDER.index(rank) if rank in ORDER else -1


def hand_cards(payload):
    cards = payload.get("cards") or {}

    return [
        {"id": card_id, **cards.get(card_id, {})}
        for card_id in payload.get("hand") or []
    ]


def turns(payload):
    return ((payload.get("round") or {}).get("turns")) or []


def played_turns(payload):
    """Turns that actually put cards down - a pass carries no cardIds."""
    return [t for t in turns(payload) if t.get("cardIds")]


def card_of(payload, card_id):
    return (payload.get("cards") or {}).get(card_id) or {}


def group_by_rank(cards):
    groups = {}

    for card in cards:
        groups.setdefault(card.get("rank"), []).append(card)

    return groups
