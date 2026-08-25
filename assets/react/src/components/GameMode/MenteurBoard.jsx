import React, { useContext, useEffect, useState } from 'react';

import {
    Card,
    PlayerList,
    SortButton,
    Stack,
} from '../components.js';
import { GameContext } from '../../Context/GameContext.js';
import { AssetsContext } from '../../Context/AssetsContext.js';
import { sortByRank } from '../../lib/sort.js';
import api from '../../lib/api.js';

// Mirrors MenteurGameMode::getRanks() on the backend - the declaration cycle
// loops from 2 back to 3. Whoever's turn it is has no free choice once a
// round is under way: the next declared rank is dictated by this cycle.
const RANK_CYCLE = ['3', '4', '5', '6', '7', '8', '9', '10', 'j', 'q', 'k', '1', '2'];
const RANK_LABELS = {
    1: 'As', 2: '2', 3: '3', 4: '4', 5: '5', 6: '6', 7: '7', 8: '8', 9: '9', 10: '10',
    j: 'Valet', q: 'Dame', k: 'Roi',
};

const nextRankInCycle = (rank) => RANK_CYCLE[(RANK_CYCLE.indexOf(rank) + 1) % RANK_CYCLE.length];
const defaultSort = (cards) => sortByRank(cards, RANK_CYCLE);

export default ({ ctx, player }) => {
    const { roomId, currentPlayer } = useContext(GameContext);
    const { getCardAsset } = useContext(AssetsContext);

    const [selectedCards, setSelectedCards] = useState([]);
    const [declaredRank, setDeclaredRank] = useState('');
    const [error, setError] = useState(null);

    const hand = ctx.players.find((p) => p.id === player?.id)?.hand ?? [];
    // Sorted by rank by default (see SortButton for the alternative "by suit" order);
    // re-applied whenever the hand changes so a freshly drawn/played card lands in place.
    const [cards, setCards] = useState(() => defaultSort([...hand]));
    const [currentSort, setCurrentSort] = useState(() => defaultSort);

    useEffect(() => {
        setCards(oldHand => (currentSort ? currentSort([...hand]) : [
            ...oldHand.filter((card) => hand.some(c => c.rank === card.rank && c.suit === card.suit)),
            ...hand.filter((card) => !oldHand.some(c => c.rank === card.rank && c.suit === card.suit)),
        ]));
    }, [hand]);

    // Current round is whatever's last in ctx.rounds - past rounds are
    // already resolved (their pile handed to a winner or loser).
    const currentRoundTurns = ctx.rounds[ctx.rounds.length - 1] ?? [];
    const lastTurn = currentRoundTurns[currentRoundTurns.length - 1] ?? null;
    const isNewRound = null === lastTurn;
    const expectedRank = isNewRound ? null : nextRankInCycle(lastTurn.data?.rank);

    const canAct = ctx.currentPlayerId === player?.id;
    const canChallenge = canAct && !isNewRound;

    // A fresh round (or the round changing after a resolved challenge) invalidates
    // whatever the player had selected/declared locally.
    useEffect(() => {
        setSelectedCards([]);
        setDeclaredRank('');
        setError(null);
    }, [ctx.rounds.length, currentRoundTurns.length]);

    const handleCard = (card) => {
        setSelectedCards((current) =>
            current.includes(card) ? current.filter((c) => c !== card) : [...current, card]);
    };

    const submit = async (data) => {
        setError(null);

        const response = await api.game.play(roomId, {
            cards: selectedCards.map((card) => card.id),
            player: currentPlayer,
            data,
        });

        if (!response.ok) {
            const errorData = await response.json();
            setError(errorData.error);
            return;
        }

        setSelectedCards([]);
        setDeclaredRank('');
    };

    const rankToPlay = isNewRound ? declaredRank : expectedRank;
    const canPlay = canAct && selectedCards.length > 0 && !!rankToPlay;

    // The pile's actual cards are known to the server (and technically present in
    // the payload), but Menteur only works if they stay secret until challenged -
    // so the board only ever shows how many cards are face-down, never what they are.
    const pileCount = currentRoundTurns.reduce((total, turn) => total + turn.cards.length, 0);
    const lastDeclarer = lastTurn && ctx.players.find((p) => p.id === lastTurn.playerId)?.username;

    return <>
        <PlayerList players={ctx.players} currentPlayerId={ctx.currentPlayerId} />
        <div className='game__right'>
            <div className='middle'>
                <div id='middle'>
                    {pileCount > 0 && (
                        <Stack
                            cards={Array.from({ length: pileCount }, (_, i) => ({ rank: 'pile', suit: String(i) }))}
                            style='drawPile'
                        />
                    )}
                </div>
                {lastTurn && (
                    <div className='info__helper'>
                        {lastDeclarer} annonce : {RANK_LABELS[lastTurn.data?.rank] ?? lastTurn.data?.rank}
                    </div>
                )}
            </div>
            <div className='bottom'>
                {player && <div className='hand__container'>
                    {canAct && isNewRound && (
                        <select value={declaredRank} onChange={(e) => setDeclaredRank(e.target.value)}>
                            <option value='' disabled>Choisir un rang</option>
                            {RANK_CYCLE.map((rank) => <option key={rank} value={rank}>{RANK_LABELS[rank]}</option>)}
                        </select>
                    )}
                    {canAct && !isNewRound && (
                        <span className='info__helper'>Rang à annoncer : {RANK_LABELS[expectedRank]}</span>
                    )}
                    {canPlay && <a className='button button--medium' onClick={() => submit({ rank: rankToPlay })}>Jouer</a>}
                    {canChallenge && <a className='button button--medium' onClick={() => submit({ challenge: true })}>Menteur !</a>}
                    {error && <div className='error'>{error}</div>}
                    <div className='hand'>
                        {cards.map((card, index) => (
                            <div key={`${card.rank}-${card.suit}`} style={{ zIndex: index }}>
                                <Card
                                    onClick={handleCard}
                                    selected={selectedCards.includes(card)}
                                    card={card}
                                    img={getCardAsset(card)}
                                    angle={0}
                                />
                            </div>
                        ))}
                    </div>
                    <SortButton setCallback={setCards} setCurrentSort={setCurrentSort} rankOrder={RANK_CYCLE} />
                </div>}
            </div>
        </div>
    </>;
}
