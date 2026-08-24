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

export default ({ ctx, player }) => {
    const { gameContext } = useContext(GameContext);
    const { animateCards, getHandRef } = useContext(AnimationContext);

    // Current round is whatever's last in ctx.rounds - past rounds are
    // already resolved to the discard pile.
    const currentRoundTurns = gameContext.rounds[gameContext.rounds.length - 1] ?? [];
    const lastTurn = currentRoundTurns[currentRoundTurns.length - 1] ?? null;
    const currentCards = lastTurn?.cards ?? [];
    const lastPlayerId = lastTurn?.playerId ?? null;

    const hand = ctx.players.find((p) => p.id === player?.id)?.hand ?? [];
    // Never actually contains real cards (drawPile identities aren't sent to
    // the front) - just enough entries to render the right number of backs.
    const drawPileCards = Array.from({ length: ctx.drawPileCount }, (_, i) => ({ rank: 'draw', suit: String(i) }));

    const lastPlayerHandRef = lastPlayerId && getHandRef(lastPlayerId);

    const playedCardRef = useRef();
    const handRef = useRef();

    useEffect(() => {
        if (player && animateCards && playedCardRef.current)  {
            const fromDiv = lastPlayerId === player.id ? handRef : lastPlayerHandRef;

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
                        onClick={() => handlePlay({ suit: name })}
                    >
                    {icon}
                    </a>
                ))}
            </div>
        ),
    }), []);

    return <>
            <PlayerList players={ctx.players} currentPlayerId={ctx.currentPlayerId} />
            <div className='game__right'>
                <div className='middle'>
                    <div id='middle'>
                        <StackedPlayedCard ref={playedCardRef} turns={currentRoundTurns} />
                        <Stack cards={drawPileCards} style='drawPile'/>
                    </div>
                </div>
                <div className='bottom'>
					{player && <Hand ref={handRef} hand={hand} order={['3', '4', '5', '6', '7', '9', '10', 'q', 'k', '1', '2', 'j', '8']} canPlay={ctx.currentPlayerId === player.id} gameActions={gameActions} />}
					{/* the chosen suit after an 8 isn't tracked by the backend yet - nothing honest to show here until it is */}
                </div>
            </div>
        </>
    ;
}
