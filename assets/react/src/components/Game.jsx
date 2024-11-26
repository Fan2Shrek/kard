import React from 'react';

import './game.css';
import Hand from './Hand/index.js';
import GameContext from '../Context/GameContext.js';
import HiddenHand from './Hand/HiddenHand.js';
import Stack from './Card/Stack.js';

export default ({ gameContext, hand, currentPlayer: user }) => {
    const ctx = JSON.parse(gameContext); 
    const currentPlayer = JSON.parse(user);

    return <>
        <GameContext gameContext={ctx} currentPlayer={currentPlayer}>
            <div className='game'>
                <HiddenHand count={5} />
                <div className='middle'>
                    <div id='middle'>
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

