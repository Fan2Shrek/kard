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

    const { displayText } = useContext(AnimationContext);

    const player = JSON.parse(user);
    const gameUrl = useMemo(() => JSON.parse(document.getElementById('mercure-game-url').textContent), []);
    const playerUrl = useMemo(() => JSON.parse(document.getElementById('mercure-game-player').textContent), []);

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
            displayText(`${data.context.data.lastPlayer} a gagné`);

            setTimeout(() => {
                window.location.href = data.url;
            }, 3000);
        },
    }), []);

    useMercure(gameUrl, gameActions);

    useMercure(playerUrl, (data) => {
        setHand(data.cards);
    });

    return <>
        <GameContext gameContext={ctx} player={player} currentPlayer={ctx.currentPlayer}>
            <Board ref={boardRef} players={ctx.players.filter((player) => player.id !== ctx.currentPlayer.id)}>
                { ctx.room.gameMode.value === 'president' && <PresidentBoard ctx={ctx} hand={hand} player={player} /> }
                { ctx.room.gameMode.value === 'crazy_eights' && <CrazyEightsBoard ctx={ctx} hand={hand} player={player} /> }
            </Board>
        </GameContext>
    </>;
}
