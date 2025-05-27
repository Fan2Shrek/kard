import React from 'react';

import Hand from '../Hand/index.js';
import HiddenHand from '../Hand/HiddenHand.js';
import PlayedCard from '../Card/PlayedCard.js';
import PlayerList from '../Player/PlayerList.js';
import Stack from '../Card/Stack.js';

export default ({ ctx, hand, player }) => {
    return <div className='game'>
            <PlayerList players={ctx.players} currentPlayer={ctx.currentPlayer} />
            <div className='game__right'>
                {ctx.players.map(player => player.id !== ctx.currentPlayer.id && <HiddenHand count={player.cardsCount} /> )}
                <div className='middle'>
                    <div id='middle'>
                        <PlayedCard cards={ctx.round.turns.map(t => t.cards).flat()} />
                        <Stack cards={ctx.discarded} />
                    </div>
                </div>
                <div className='bottom'>
                    <Hand hand={hand} canPlay={ctx.currentPlayer.id === player.id} />
                </div>
            </div>
        </div>
    ;
}
