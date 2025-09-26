import React, { useMemo, useRef, useContext, useEffect } from 'react';

import {
    Hand,
    HiddenHand,
    PlayerList,
    Stack,
} from '../components.js';
import { AnimationContext } from '../../Context/AnimationContext.js';
import { GameContext } from '../../Context/GameContext.js';
import { suitsIcons } from '../../enum.js';
import StackedPlayedCard from '../Card/Stack/StackedPlayedCard.js';

export default ({ ctx, hand, player }) => {
    const { gameContext: { currentCards } } = useContext(GameContext);
    const { animateCards, getHandRef } = useContext(AnimationContext);

    const lastPlayerHandRef = ctx.data.lastPlayer && getHandRef(ctx.data.lastPlayer);

    const playedCardRef = useRef();
    const handRef = useRef();

    useEffect(() => {
        if (player && animateCards && playedCardRef.current)  {
            const fromDiv = ctx.data.lastPlayer === player.id ? handRef : lastPlayerHandRef;

            fromDiv && fromDiv.current && animateCards(currentCards, fromDiv.current, playedCardRef.current );
        }
    }, [animateCards, currentCards]);

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

    return <>
            <PlayerList players={ctx.players} currentPlayer={ctx.currentPlayer} />
            <div className='game__right'>
                <div className='middle'>
                    <div id='middle'>
                        <StackedPlayedCard ref={playedCardRef} turns={ctx.round.turns} />
                        <Stack cards={ctx.drawPile} style='drawPile'/>
                    </div>
                </div>
                <div className='bottom'>
					{player && <Hand ref={handRef} hand={hand} order={['3', '4', '5', '6', '7', '9', '10', 'q', 'k', '1', '2', 'j', '8']} canPlay={ctx.currentPlayer.id === player.id} gameActions={gameActions} />}
                </div>
            </div>
        </>
    ;
}
