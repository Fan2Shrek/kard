import React, { useMemo } from 'react';

import DrawPile from '../Card/DrawPile.js';
import Hand from '../Hand/index.js';
import HiddenHand from '../Hand/HiddenHand.js';
import PlayedCard from '../Card/PlayedCard.js';
import PlayerList from '../Player/PlayerList.js';
import Stack from '../Card/Stack.js';

import { suitsIcons } from '../../enum.js';

export default ({ ctx, hand, player }) => {
    const gameActions = useMemo(() => (handlePlay) => ({
        8: (
            <div>
                {Object.entries(suitsIcons).map(([ name, icon ]) => (
                    <a
                        key={name}
                        className="button button--medium"
                        onClick={() => handlePlay({ name })}
                    >
                    {icon}
                    </a>
                ))}
            </div>
        ),
    }), []);

    return <div className='game'>
            <PlayerList players={ctx.players} currentPlayer={ctx.currentPlayer} />
            <div className='game__right'>
                {ctx.players.map(player => player.id !== ctx.currentPlayer.id && <HiddenHand count={player.cardsCount} /> )}
                <div className='middle'>
                    <div id='middle'>
                        <Stack cards={ctx.round.turns.map(t => t.cards).flat()} />
                        <DrawPile cards={ctx.drawPile} />
                    </div>
                </div>
                <div className='bottom'>
                    <Hand hand={hand} canPlay={ctx.currentPlayer.id === player.id} gameActions={gameActions} />
                </div>
            </div>
        </div>
    ;
}
