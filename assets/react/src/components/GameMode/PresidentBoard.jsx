import React, { useContext, useEffect, useRef } from 'react';

import { GameContext } from '../../Context/GameContext.js';
import { AnimationContext } from '../../Context/AnimationContext.js';
import Hand from '../Hand/index.js';
import Card from '../Card.js';
import HiddenHand from '../Hand/HiddenHand.js';
import PlayedCard from '../Card/PlayedCard.js';
import PlayerList from '../Player/PlayerList.js';
import Stack from '../Card/Stack.js';

export default ({ ctx, hand, player }) => {
    const { gameContext: { currentCards: [currentCards] } } = useContext(GameContext);
    const { animateCard } = useContext(AnimationContext);

    const playedCardRef = useRef();
    const handRef = useRef();

    useEffect(() => {
        if (animateCard && currentCards && playedCardRef.current && handRef.current) {
            animateCard(currentCards, handRef.current.getBoundingClientRect(), playedCardRef.current.getBoundingClientRect(), () => console.log('Animation done'));
        }
    }, [animateCard, currentCards]);

    return <div className='game'>
            <PlayerList players={ctx.players} currentPlayer={ctx.currentPlayer} />
            <div className='game__right'>
                {ctx.players.map(player => player.id !== ctx.currentPlayer.id && <HiddenHand count={player.cardsCount} /> )}
                <div className='middle'>
                    <div id='middle'>
                        <PlayedCard ref={playedCardRef} cards={ctx.round.turns.map(t => t.cards).flat()} />
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
