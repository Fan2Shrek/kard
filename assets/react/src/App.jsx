import React from 'react';

import { Game } from './components/components.js';
import AssetsContext from './Context/AssetsContext.js';
import AnimationContext from './Context/AnimationContext.js';

export default ({ gameContext, assets, hand, player }) => {
    return <>
        <AssetsContext assets={assets}>
            <AnimationContext>
                <Game gameContext={gameContext} hand={hand} player={player} />
            </AnimationContext>
        </AssetsContext>
    </>;
}
