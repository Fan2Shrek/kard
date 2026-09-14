import React, { useContext, useEffect, useRef, useState } from 'react';

import { Card } from '../components.js';
import { GameContext } from '../../Context/GameContext.js';
import { AssetsContext } from '../../Context/AssetsContext.js';
import api from '../../lib/api.js';

import './solitaireBoard.css';

const COLUMNS = 7;
const SUITS = ['h', 'd', 'c', 's'];
const RANKS = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', 'j', 'q', 'k'];

const columnKey = (index, half) => `tableau_${index}_${half}`;

const formatDuration = (seconds) => {
    const minutes = Math.floor(seconds / 60);

    return `${minutes}:${String(seconds % 60).padStart(2, '0')}`;
};

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
    const { roomId, applyState } = useContext(GameContext);
    const { getCardAsset, getBackAsset } = useContext(AssetsContext);

    // { from: pile key, cards: [Card] } - a run picked up but not yet dropped
    const [selection, setSelection] = useState(null);
    const [error, setError] = useState(null);
    const [elapsed, setElapsed] = useState(0);

    // a drop fires long after its dragstart, but reading the payload from a ref
    // rather than from state keeps the two halves of the gesture in step
    const dragged = useRef(null);

    const pile = (key) => ctx.piles?.[key] ?? [];

    // no card is face down once the game is decided: only clicks remain
    const canFinish = Array.from({ length: COLUMNS }, (_, i) => pile(columnKey(i, 'down')))
        .every((cards) => 0 === cards.length);

    useEffect(() => {
        if (!ctx.startedAt) {
            return;
        }

        const started = new Date(ctx.startedAt).getTime();
        const tick = () => setElapsed(Math.max(0, Math.floor((Date.now() - started) / 1000)));

        tick();
        const timer = setInterval(tick, 1000);

        return () => clearInterval(timer);
    }, [ctx.startedAt]);

    const send = async (cards, to) => {
        setError(null);
        setSelection(null);

        const response = await api.game.play(roomId, { cards: cards.map((card) => card.id), data: { to } });

        if (!response.ok) {
            const errorData = await response.json();
            setError(errorData.error);

            return;
        }

        applyState(await response.json());
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

    const startDrag = (event, key, cards, index) => {
        dragged.current = { from: key, cards: cards.slice(index) };
        setSelection(dragged.current);
        event.dataTransfer.effectAllowed = 'move';
        // Firefox ignores a drag that carries no data at all
        event.dataTransfer.setData('text/plain', cards[index].id);
    };

    const dropProps = (key, cards) => ({
        onDragOver: (event) => {
            const payload = dragged.current;

            // not calling preventDefault is what refuses the drop, so an illegal
            // target shows the "no drop" cursor and never reaches the server
            if (payload && payload.from !== key && accepts(payload.cards, cards[cards.length - 1] ?? null, key)) {
                event.preventDefault();
            }
        },
        onDrop: (event) => {
            event.preventDefault();

            const payload = dragged.current;
            dragged.current = null;

            if (payload) {
                send(payload.cards, key);
            }
        },
    });

    const isSelected = (card) => Boolean(selection?.cards.includes(card));

    const isTarget = (key, cards) => Boolean(
        selection && selection.from !== key && accepts(selection.cards, cards[cards.length - 1] ?? null, key)
    );

    // zIndex has to keep climbing past the face-down half of the column,
    // otherwise the last face-down card covers the first face-up one
    const renderCard = (card, key, cards, index, zIndex = index) => (
        <div
            className="solitaire__slot-card"
            style={{ zIndex }}
            key={card.id}
            draggable
            onDragStart={(event) => startDrag(event, key, cards, index)}
            onDragEnd={() => { dragged.current = null; }}
        >
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

        <div className="solitaire__actions">
            <a
                className="button button--medium"
                href={`/room/start/${roomId}`}
                onClick={(event) => !confirm('Recommencer une nouvelle partie ?') && event.preventDefault()}
            >
                Recommencer
            </a>
            <a className="button button--medium" href={`/room/leave/${roomId}`}>Quitter</a>
        </div>

        <div className="solitaire__status">
            <span>{formatDuration(elapsed)}</span>
            <span>{ctx.moves} coup{1 < ctx.moves ? 's' : ''}</span>
            {canFinish && <a className="button button--medium" onClick={() => send([], 'auto')}>Terminer</a>}
        </div>

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
                        {...dropProps(key, cards)}
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
                    {...dropProps(key, faceUp)}
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
