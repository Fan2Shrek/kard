from ._common import hand_cards, turns

# ponytail: annonce toujours le rang attendu et ne conteste jamais. Un bot honnete
# au menteur est un mauvais bot - le bluff et le challenge sont a ajouter ici.

# MenteurGameMode::getRanks() - its own cycle, not the trick-taking ORDER
RANKS = ["3", "4", "5", "6", "7", "8", "9", "10", "j", "q", "k", "1", "2"]


def expected_rank(payload):
    all_turns = turns(payload)

    if not all_turns:
        return RANKS[0]

    previous = (all_turns[-1].get("data") or {}).get("rank")

    if previous not in RANKS:
        return RANKS[0]

    return RANKS[(RANKS.index(previous) + 1) % len(RANKS)]


def move(payload):
    cards = hand_cards(payload)

    if not cards:
        return [], {}

    rank = expected_rank(payload)
    # play the truth when we can, otherwise lie with the first card we hold
    truthful = [c for c in cards if c.get("rank") == rank]
    chosen = truthful[0] if truthful else cards[0]

    return [chosen["id"]], {"rank": rank}
