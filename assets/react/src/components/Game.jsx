import React, { useMemo, useState } from 'react';

import './game.css';
import Hand from './Hand/index.js';
import useMercure from '../hook/useMercure.js';
import GameContext from '../Context/GameContext.js';
import HiddenHand from './Hand/HiddenHand.js';
import Stack from './Card/Stack.js';

export default ({ gameContext, hand: currentHand, currentPlayer: user }) => {
    const [ctx, setCtx] = useState(JSON.parse(gameContext));
    const [hand, setHand] = useState(currentHand);
    const currentPlayer = JSON.parse(user);
    const gameUrl = useMemo(() => JSON.parse(document.getElementById('mercure-game-url').textContent), []);
    const playerUrl = useMemo(() => JSON.parse(document.getElementById('mercure-game-player').textContent), []);

    useMercure(gameUrl, (data) => {
        setCtx(data);
    });

    useMercure(playerUrl, (data) => {
        setHand(data.cards);
    });

    return <>
        <GameContext gameContext={ctx} currentPlayer={currentPlayer}>
            <div className='game'>
                <HiddenHand count={5} />
                <div className='middle'>
                    <div id='middle'>
                        // @todo own component 
                        <Hand hand={ctx.currentCards} />
                        <Stack cards={ctx.discarded} />
                    </div>
                </div>
                <div className='bottom'>
                    <Hand hand={hand} />
                </div>
            </div>
        </GameContext>
    </>;
}

