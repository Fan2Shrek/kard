import React, { useContext, useEffect, useMemo, useState, useRef } from 'react';

import { GameContext } from '../../Context/GameContext.js';
import { AnimationContext } from '../../Context/AnimationContext.js';
import {
    Hand,
    HiddenHand,
    PlayedCard,
    PlayerList,
    Card,
    Stack,
} from '../components.js';

export default ({ ctx, hand, player }) => {
    const { gameContext: { currentCards } } = useContext(GameContext);
    const { animateCards, getHandRef } = useContext(AnimationContext);

    const playedCardRef = useRef();
    const handRef = useRef();

    const lastPlayerHandRef = ctx.data.lastPlayer && getHandRef(ctx.data.lastPlayer);

    useEffect(() => {
        if (animateCards && currentCards && playedCardRef.current)  {
            const fromDiv = ctx.data.lastPlayer === player.id ? handRef : lastPlayerHandRef;

            fromDiv && fromDiv.current && animateCards(currentCards, fromDiv.current, playedCardRef.current );
        }
    }, [animateCards, currentCards]);

    return <div className='game'>
            <PlayerList players={ctx.players} currentPlayer={ctx.currentPlayer} />
            <div className='game__right'>
                <div className='middle'>
                    <div id='middle'>
                        <PlayedCard ref={playedCardRef} turns={ctx.round.turns} />
                        <Stack cards={ctx.discarded} />
                    </div>
                </div>
                <div className='bottom'>
                    <Hand ref={handRef} hand={hand} canPlay={ctx.currentPlayer.id === player.id || ctx.data.fastPlay} />
                </div>
            </div>
        </div>
    ;
}
