import React, { useContext, useEffect, useMemo, useRef, useState } from 'react';

import useMercure from '../hook/useMercure.js';
import GameContext from '../Context/GameContext.js';
import { AnimationContext } from '../Context/AnimationContext.js';
import {
    Board,
    CrazyEightsBoard,
    MenteurBoard,
    PresidentBoard,
} from './components.js';

import './game.css';

// Ordered by priority: the first matching event in a batch wins the announcement.
const eventMessages = {
    round_ended: () => 'Fin du tour',
    card_or_nothing_called: (payload) => `${payload.rank} ou rien`,
    turn_played: (payload, ctx) => {
        if (0 !== (payload.cards ?? []).length) {
            return null;
        }

        const username = ctx.players.find((p) => p.id === ctx.currentPlayerId)?.username;

        return username ? `${username} passe son tour` : null;
    },
};

const buildAnnouncement = (events, ctx) => {
    for (const type of Object.keys(eventMessages)) {
        const event = events.find((e) => e.type === type);

        if (!event) {
            continue;
        }

        const message = eventMessages[type](event.payload ?? {}, ctx);

        if (message) {
            return message;
        }
    }

    return null;
};

export default ({ gameContext, player: userJson, gameMode, roomId }) => {
    const [ctx, setCtx] = useState(JSON.parse(gameContext));
    const [animate, setAnimate] = useState(false);

    const { displayText } = useContext(AnimationContext);

    const player = useMemo(() => (userJson ? JSON.parse(userJson) : null), [userJson]);
    const gameUrl = useMemo(() => JSON.parse(document.getElementById('mercure-game-url').textContent), []);

    // onGameEvent is memoized once (see below) so it closes over the ctx value
    // from mount. A ref lets it always read the latest state - needed to know
    // who currentPlayerId was *before* the refetch overwrites it.
    const ctxRef = useRef(ctx);
    useEffect(() => {
        ctxRef.current = ctx;
    }, [ctx]);

    // The topic carries two different message shapes: ContinueRoomSubscriber's
    // { action: 'end', data: { context, url } } when the game is over, and
    // EventPublisher's { events } on every play (state is deliberately not
    // published there - it's the raw, unredacted GameState including every
    // player's hand - so a play just triggers a proper refetch instead).
    const onGameEvent = useMemo(() => (data) => {
        if ('end' === data.action) {
            displayText(`${data.data.context.winner.username} a gagné`);
            setAnimate(true);

            setTimeout(() => {
                window.location.href = data.data.url;
            }, 3000);

            return;
        }

        if (data.events) {
            const message = buildAnnouncement(data.events, ctxRef.current);

            if (message) {
                displayText(message);
            }

            fetch(`/api/game/${roomId}`, { credentials: 'same-origin' })
                .then((response) => response.json())
                .then(setCtx);
        }
    }, []);

    useMercure(gameUrl, onGameEvent);

    return <div class={`board${animate ? ' bordel': ''}`}>
        <GameContext gameContext={ctx} player={player} roomId={roomId}>
            <Board players={ctx.players.filter((gamePlayer) => player?.id !== gamePlayer.id)}>
                { 'president' === gameMode && <PresidentBoard ctx={ctx} player={player} /> }
                { 'crazy_eights' === gameMode && <CrazyEightsBoard ctx={ctx} player={player} /> }
                { 'menteur' === gameMode && <MenteurBoard ctx={ctx} player={player} /> }
            </Board>
        </GameContext>
    </div>;
}
