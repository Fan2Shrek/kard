import React, { useContext, useState } from 'react';

import { Card } from '../components.js';
import { GameContext } from '../../Context/GameContext.js';
import { AssetsContext } from '../../Context/AssetsContext.js';
import api from '../../lib/api.js';

import './solitaireBoard.css';

const COLUMNS = 7;
const SUITS = ['h', 'd', 'c', 's'];
const RANKS = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'j', 'q', 'k'];

const columnKey = (index, half) => `tableau_${index}_${half}`;

const rank = (card) => RANKS.indexOf(card.rank) + 1;
const isRed = (card) => 'h' === card.suit || 'd' === card.suit;

// Mirrors SolitaireGameMode's rules so the board can show where a picked-up
// card is allowed to land. The server still decides - this only lights up zones.
const accepts = (cards, top, key) => {
    const card = cards[0];

    if (key.startsWith('foundation_')) {
        return 1 === cards.length
            && `foundation_${card.suit}` === key
            && rank(card) === (top ? rank(top) + 1 : 1);
    }

    return top
        ? rank(card) === rank(top) - 1 && isRed(card) !== isRed(top)
        : 'k' === card.rank;
};

export default ({ ctx }) => {
    const { roomId } = useContext(GameContext);
    const { getCardAsset, getBackAsset } = useContext(AssetsContext);

    // { from: pile key, cards: [Card] } - a run picked up but not yet dropped
    const [selection, setSelection] = useState(null);
    const [error, setError] = useState(null);

    const pile = (key) => ctx.piles?.[key] ?? [];

    const send = async (cards, to) => {
        setError(null);
        setSelection(null);

        const response = await api.game.play(roomId, { cards: cards.map((card) => card.id), data: { to } });

        if (!response.ok) {
            const errorData = await response.json();
            setError(errorData.error);
        }
    };

    // First click picks a card up (with the face-up run below it), second click
    // drops the selection on whatever pile was clicked.
    const handleCard = (key, cards, index) => {
        if (selection) {
            send(selection.cards, key);

            return;
        }

        setSelection({ from: key, cards: cards.slice(index) });
    };

    const handlePile = (key) => selection && send(selection.cards, key);

    const isSelected = (card) => Boolean(selection?.cards.includes(card));

    const isTarget = (key, cards) => Boolean(
        selection && selection.from !== key && accepts(selection.cards, cards[cards.length - 1] ?? null, key)
    );

    // zIndex has to keep climbing past the face-down half of the column,
    // otherwise the last face-down card covers the first face-up one
    const renderCard = (card, key, cards, index, zIndex = index) => (
        <div className="solitaire__slot-card" style={{ zIndex }} key={card.id}>
            <Card
                card={card}
                img={getCardAsset(card)}
                selected={isSelected(card)}
                lift={16}
                onClick={() => handleCard(key, cards, index)}
            />
        </div>
    );

    const waste = ctx.discardPile ?? [];
    const wasteTop = waste[waste.length - 1] ?? null;

    return <div className="solitaire">
        {error && <div className="error">{error}</div>}

        <div className="solitaire__top">
            <div className="solitaire__stock" onClick={() => send([], 'stock')}>
                {ctx.drawPileCount > 0
                    ? <Card card={{ id: 'stock' }} img={getBackAsset()} clickable={false} />
                    : <div className="solitaire__empty" title="Recycler la défausse" />}
            </div>

            <div className="solitaire__waste">
                {wasteTop && renderCard(wasteTop, 'waste', [wasteTop], 0)}
            </div>

            <div className="solitaire__foundations">
                {SUITS.map((suit) => {
                    const key = `foundation_${suit}`;
                    const cards = pile(key);
                    const top = cards[cards.length - 1] ?? null;

                    return <div
                        className={`solitaire__foundation${isTarget(key, cards) ? ' solitaire__foundation--target' : ''}`}
                        key={key}
                        onClick={() => handlePile(key)}
                    >
                        {top
                            ? <Card card={top} img={getCardAsset(top)} clickable={false} />
                            : <div className="solitaire__empty" />}
                    </div>;
                })}
            </div>
        </div>

        <div className="solitaire__tableau">
            {Array.from({ length: COLUMNS }, (_, index) => {
                const key = columnKey(index, 'up');
                const faceUp = pile(key);
                // face-down cards come back as nulls - a count, not identities
                const faceDown = pile(columnKey(index, 'down'));

                return <div
                    className={`solitaire__column${isTarget(key, faceUp) ? ' solitaire__column--target' : ''}`}
                    key={key}
                    onClick={() => 0 === faceUp.length && handlePile(key)}
                >
                    {faceDown.map((_ignored, i) => (
                        <div className="solitaire__slot-card solitaire__slot-card--down" style={{ zIndex: i }} key={`down-${i}`}>
                            <Card card={{ id: `${key}-down-${i}` }} img={getBackAsset()} clickable={false} />
                        </div>
                    ))}
                    {faceUp.map((card, i) => renderCard(card, key, faceUp, i, faceDown.length + i))}
                    {0 === faceUp.length && 0 === faceDown.length && <div className="solitaire__empty" />}
                </div>;
            })}
        </div>
    </div>;
}
