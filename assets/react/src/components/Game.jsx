import React, { useContext, useMemo, useEffect, useRef, useState,  } from 'react';

import useMercure from '../hook/useMercure.js';
import GameContext from '../Context/GameContext.js';
import { AnimationContext } from '../Context/AnimationContext.js';
import {
    Board,
    CrazyEightsBoard,
    PresidentBoard,
} from './components.js';

import './game.css';

const eventMessages = {
    card_drawn: (data) => data.forced
        ? `${data.player.username} pioche ${data.count} cartes`
        : `${data.player.username} pioche une carte`,
    suit_changed: (data) => `Changement de couleur en ${data.suit.symbol}`,
    play_order_reversed: () => 'Changement de sens !',
    turn_skipped: (data) => `${data.player.username} saute son tour`,
    round_ended: () => 'Fin du tour',
    card_or_nothing_called: (data) => data.isCallForFour ? 'Appel aux quatre' : `${data.rank.value} ou rien`,
};

export default ({ gameContext, hand: currentHand, player: user }) => {
    const [ctx, setCtx] = useState(JSON.parse(gameContext));
    const [hand, setHand] = useState(currentHand);
	const [animate, setAnimate] = useState(false);

    const { displayText } = useContext(AnimationContext);

    const player = JSON.parse(user);
    const gameUrl = useMemo(() => JSON.parse(document.getElementById('mercure-game-url').textContent), []);
    // the room id never changes during the session, captured once from the initial state
    const roomId = useMemo(() => ctx.id, []);

    const boardRef = useRef(null);

    const onGameEvent = useMemo(() => (data) => {
        if (data.type) {
            const buildMessage = eventMessages[data.type];

            if (buildMessage) {
                displayText(buildMessage(data.data));
            }

            fetch(`/api/game/${roomId}`, { credentials: 'same-origin' })
                .then((response) => response.json())
                .then(({ state, hand: newHand }) => {
                    setCtx(state);

                    if (null !== newHand) {
                        setHand(newHand);
                    }
                });

            return;
        }

        if ('end' === data.action) {
            setCtx(data.data.context);
            displayText(`${data.data.context.winner.username} a gagné`);
			setAnimate(true);

            setTimeout(() => {
                window.location.href = data.data.url;
            }, 3000);
        }
    }, []);

    useMercure(gameUrl, onGameEvent);

    return <div class={`board${animate ? ' bordel': ''}`}>
        <GameContext gameContext={ctx} player={player} currentPlayer={ctx.currentPlayer}>
            <Board ref={boardRef} players={ctx.players.filter((gamePlayer) => player?.id !== gamePlayer.id)}>
                { ctx.room.gameMode.value === 'president' && <PresidentBoard ctx={ctx} hand={hand} player={player} /> }
                { ctx.room.gameMode.value === 'crazy_eights' && <CrazyEightsBoard ctx={ctx} hand={hand} player={player} /> }
            </Board>
        </GameContext>
    </div>;
}
