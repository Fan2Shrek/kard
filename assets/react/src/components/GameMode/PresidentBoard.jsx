import React, { useContext, useEffect, useState, useRef } from 'react';

import { GameContext } from '../../Context/GameContext.js';
import { AnimationContext } from '../../Context/AnimationContext.js';
import Hand from '../Hand/index.js';
import Card from '../Card.js';
import HiddenHand from '../Hand/HiddenHand.js';
import PlayedCard from '../Card/PlayedCard.js';
import PlayerList from '../Player/PlayerList.js';
import Stack from '../Card/Stack.js';

export default ({ ctx, hand, player }) => {
    const { gameContext: { currentCards } } = useContext(GameContext);
    const { animateCards } = useContext(AnimationContext);

    const playedCardRef = useRef();
    const handRef = useRef();

    useEffect(() => {
        if (animateCards && currentCards && playedCardRef.current && handRef.current) {
            animateCards(currentCards, handRef.current, playedCardRef.current );
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
                    <Hand ref={handRef} hand={hand} canPlay={ctx.currentPlayer.id === player.id} />
                </div>
            </div>
        </div>
    ;
}
