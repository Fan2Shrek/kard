"""Helpers shared by every strategy.

PHP sends the same redacted view the front gets (App\\Domain\\DTO\\GameStateDTO,
viewer = the bot): every player's hand is null except ours.

    {
      "players": [{"id": ..., "username": ..., "score": 0, "cardsCount": 5,
                   "hand": [{"id": ..., "rank": "8", "suit": "h"}] or null}, ...],
      "playerOrder": [...], "currentPlayerId": ...,
      "rounds": [[{"playerId": ..., "cards": [<card>, ...], "data": {...}}, ...], ...],
      "discardPile": [<card>, ...], "drawPileCount": 12, "everyoneCanPlay": false
    }

Only the last round matters to the strategies; turns carry their cards inline,
raw, so each mode derives what it needs (the trick to beat, a declared rank, a
penalty) rather than PHP guessing per mode.

A move is (card_ids, data). An empty list means "I play nothing" - the game mode
decides what that means (draw, pass, ...) and may well reject it.
"""

# Rank values as stored by App\Enum\Card\Rank - note ace is "1" and face cards
# are lowercase. Ordered weakest to strongest for trick-taking games.
ORDER = ["3", "4", "5", "6", "7", "8", "9", "10", "j", "q", "k", "1", "2"]


def rank_value(rank):
    return ORDER.index(rank) if rank in ORDER else -1


def me(payload):
    """We are the only player whose hand isn't redacted."""
    for player in payload.get("players") or []:
        if player.get("hand") is not None:
            return player

    return {}


def hand_cards(payload):
    return me(payload).get("hand") or []


def turns(payload):
    """The current round only - previous rounds are history, nobody reads them."""
    rounds = payload.get("rounds") or []

    return rounds[-1] if rounds else []


def played_turns(payload):
    """Turns that actually put cards down - a pass carries no cards."""
    return [t for t in turns(payload) if t.get("cards")]


def group_by_rank(cards):
    groups = {}

    for card in cards:
        groups.setdefault(card.get("rank"), []).append(card)

    return groups
