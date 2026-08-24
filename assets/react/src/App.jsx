import React from 'react';

import { Game } from './components/components.js';
import AssetsContext from './Context/AssetsContext.js';
import AnimationContext from './Context/AnimationContext.js';

export default ({ gameContext, assets, player, gameMode, roomId }) => {
    return <>
        <AssetsContext assets={assets}>
            <AnimationContext>
                <Game gameContext={gameContext} player={player} gameMode={gameMode} roomId={roomId} />
            </AnimationContext>
        </AssetsContext>
    </>;
}
