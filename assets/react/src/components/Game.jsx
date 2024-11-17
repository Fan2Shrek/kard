import React from 'react';

import './game.css';
import Hand from './Hand/index.js';
import GameContext from '../Context/GameContext.js';
import HiddenHand from './Hand/HiddenHand.js';
import Mercure from './turbo/mercure.js';
import PlayerChoicePlaceHolder from './PlayerChoicePlaceHolder.js';

export default ({ gameContext, hand, currentPlayer: user }) => {
    const ctx = JSON.parse(gameContext); 
    const currentPlayer = JSON.parse(user);

    return <>
        <GameContext gameContext={ctx} currentPlayer={currentPlayer}>
            <div className='game'>
                <HiddenHand count={5} />
                <div className='middle'>
                    <Mercure topic={`/room/${ctx.room.id}`} />
                    <Mercure topic={`/room/${ctx.room.id}/${currentPlayer.id}`} />
                    <div id='middle'>
                        {ctx.room.players.map((player) => 
                            <div key={player.id} id={`placeholder-${player.id}`}>
                                <PlayerChoicePlaceHolder player={player} />
                            </div>
                        )}
                    </div>
                </div>
                <div className='bottom'>
                    <Hand hand={hand} />
                </div>
            </div>
        </GameContext>
    </>;
}

