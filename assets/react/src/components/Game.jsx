import React from 'react';

import Hand from './Hand/index.js';
import GameContext from '../Context/GameContext.js';

export default ({ gameContext, hand }) => {
    return <>
        <GameContext gameContext={gameContext}>
            <div className='game'>
                <Hand hand={hand} />
            </div>
        </GameContext>
    </>;
}

