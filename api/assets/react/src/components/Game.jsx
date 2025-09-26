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

export default ({ gameContext, hand: currentHand, player: user }) => {
    const [ctx, setCtx] = useState(JSON.parse(gameContext));
    const [hand, setHand] = useState(currentHand);
	const [animate, setAnimate] = useState(false);

    const { displayText } = useContext(AnimationContext);

    const player = JSON.parse(user);
    const gameUrl = useMemo(() => JSON.parse(document.getElementById('mercure-game-url').textContent), []);
    const playerUrl = useMemo(() => player ? JSON.parse(document.getElementById('mercure-game-player').textContent) : null, []);

    const boardRef = useRef(null);

    const gameActions = useMemo(() => ({
        play: (data) => {
            setCtx(data);
            const currentCards = data.currentCards;
        },
        message: (data) => {
            displayText(data.text);
        },
        end: (data) => {
            setCtx(data.context);
            displayText(`${data.context.winner.username} a gagné`);
			setAnimate(true);

            setTimeout(() => {
                window.location.href = data.url;
            }, 3000);
        },
    }), []);

    useMercure(gameUrl, gameActions);

    useMercure(playerUrl, (data) => {
        setHand(data.cards);
    });

    return <div class={`board${animate ? ' bordel': ''}`}>
        <GameContext gameContext={ctx} player={player} currentPlayer={ctx.currentPlayer}>
            <Board ref={boardRef} players={ctx.players.filter((gamePlayer) => player?.id !== gamePlayer.id)}>
                { ctx.room.gameMode.value === 'president' && <PresidentBoard ctx={ctx} hand={hand} player={player} /> }
                { ctx.room.gameMode.value === 'crazy_eights' && <CrazyEightsBoard ctx={ctx} hand={hand} player={player} /> }
            </Board>
        </GameContext>
    </div>;
}
