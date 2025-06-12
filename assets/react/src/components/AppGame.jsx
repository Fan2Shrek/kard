import React from 'react';

import Game from './Game.js';
import AssetsContext from '../Context/AssetsContext.js';

export default ({ gameContext, assets, hand, player }) => {
    return <>
        <AssetsContext assets={assets}>
            <Game gameContext={gameContext} hand={hand} player={player} />
        </AssetsContext>
    </>;
}
