from ._common import group_by_rank, hand_cards, played_turns, rank_value

# ponytail: joue le plus petit groupe legal. Pas de bombe, pas de garde de 2.
# C'est ici qu'une vraie strategie se branche.


def card_or_nothing_rank(plays):
    """Two same-rank plays in a row lock the round on that rank.

    Mirrors PresidentGameMode::getCardOrNothingRank().
    """
    if len(plays) < 2:
        return None

    first = plays[-2]["cards"][0].get("rank")
    second = plays[-1]["cards"][0].get("rank")

    return first if first == second else None


def move(payload):
    cards = hand_cards(payload)

    if not cards:
        return [], {}

    plays = played_turns(payload)

    # opening the round: the weakest single we hold, but never a 2 (it would end
    # the round we just opened)
    if not plays:
        singles = sorted(
            (c for c in cards if c.get("rank") != "2"), key=lambda c: rank_value(c["rank"])
        )

        return ([singles[0]["id"]], {}) if singles else ([cards[0]["id"]], {})

    base = plays[-1]["cards"][0]
    count = len(plays[-1]["cards"])
    locked = card_or_nothing_rank(plays)

    groups = group_by_rank(cards)

    if locked is not None:
        # only the locked rank is playable, and still in the right quantity
        group = groups.get(locked) or []

        return ([c["id"] for c in group[:count]], {}) if len(group) >= count else ([], {})

    candidates = [
        (rank, group)
        for rank, group in groups.items()
        if len(group) >= count and rank_value(rank) >= rank_value(base.get("rank"))
    ]

    if not candidates:
        return [], {}

    rank, group = min(candidates, key=lambda item: rank_value(item[0]))

    return [c["id"] for c in group[:count]], {}
