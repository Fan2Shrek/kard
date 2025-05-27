import React, { useMemo, useState, useEffect } from 'react';

import './game.css';
import useMercure from '../hook/useMercure.js';
import GameContext from '../Context/GameContext.js';
import Text from './Animation/Text.js';
import PresidentBoard from './GameMode/PresidentBoard.js';
import CrazyEightsBoard from './GameMode/CrazyEightsBoard.js';

export default ({ gameContext, hand: currentHand, player: user }) => {
    const [ctx, setCtx] = useState(JSON.parse(gameContext));
    const [hand, setHand] = useState(currentHand);
    const [text, setText] = useState(null);
    const [key, setKey] = useState(0);

    const player = JSON.parse(user);
    const gameUrl = useMemo(() => JSON.parse(document.getElementById('mercure-game-url').textContent), []);
    const playerUrl = useMemo(() => JSON.parse(document.getElementById('mercure-game-player').textContent), []);

    const gameActions = useMemo(() => ({
        play: (data) => {
            setCtx(data);
        },
        message: (data) => {
            setKey((key) => key + 1);
            setText(data.text);
        },
        end: (data) => {
            setCtx(data.context);
            setText(`${data.context.currentPlayer.username} a gagné`);

            setTimeout(() => {
                window.location.href = data.url;
            }, 3000);
        },
    }), []);

    useMercure(gameUrl, gameActions);

    useMercure(playerUrl, (data) => {
        setHand(data.cards);
    });

    console.log('gameContext', ctx);

    // @todo display one hand per player
    return <>
        <GameContext gameContext={ctx} player={player} currentPlayer={ctx.currentPlayer}>
            { text && <Text key={key} text={text} /> }
            { ctx.room.gameMode.value === 'president' && <PresidentBoard ctx={ctx} hand={hand} player={player} /> }
            { ctx.room.gameMode.value === 'crazy_eights' && <CrazyEightsBoard ctx={ctx} hand={hand} player={player} /> }
        </GameContext>
    </>;
}
