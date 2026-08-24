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

export default ({ ctx, player }) => {
    const { gameContext } = useContext(GameContext);
    const { animateCards, getHandRef } = useContext(AnimationContext);

    const playedCardRef = useRef();
    const handRef = useRef();

    // Current round is whatever's last in ctx.rounds - past rounds are
    // already resolved to the discard pile.
    const currentRoundTurns = gameContext.rounds[gameContext.rounds.length - 1] ?? [];
    const lastTurn = currentRoundTurns[currentRoundTurns.length - 1] ?? null;
    const currentCards = lastTurn?.cards ?? [];
    const lastPlayerId = lastTurn?.playerId ?? null;

    const hand = ctx.players.find((p) => p.id === player?.id)?.hand ?? [];

    const lastPlayerHandRef = lastPlayerId && getHandRef(lastPlayerId);

    useEffect(() => {
        if (player && animateCards && currentCards && playedCardRef.current)  {
            const fromDiv = lastPlayerId === player.id ? handRef : lastPlayerHandRef;

            fromDiv && fromDiv.current && animateCards(currentCards, fromDiv.current, playedCardRef.current );
        }
    }, [animateCards, currentCards]);

    return <>
            <PlayerList players={ctx.players} currentPlayerId={ctx.currentPlayerId} />
            <div className='game__right'>
                <div className='middle'>
                    <div id='middle'>
                        <PlayedCard ref={playedCardRef} turns={currentRoundTurns} />
                        <Stack cards={ctx.discardPile} />
                    </div>
                </div>
                <div className='bottom'>
					{player && <Hand ref={handRef} order={['3', '4', '5', '6', '7', '8', '9', '10', 'j', 'q', 'k', '1', '2']} hand={hand} canPlay={ctx.currentPlayerId === player.id || ctx.everyoneCanPlay} />}
                </div>
            </div>
        </>
    ;
}
