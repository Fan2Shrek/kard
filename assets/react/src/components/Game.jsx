import React, { useContext, useMemo, useState } from 'react';

import useMercure from '../hook/useMercure.js';
import GameContext from '../Context/GameContext.js';
import { AnimationContext } from '../Context/AnimationContext.js';
import {
    Board,
    CrazyEightsBoard,
    PresidentBoard,
} from './components.js';

import './game.css';

export default ({ gameContext, player: userJson, gameMode, roomId }) => {
    const [ctx, setCtx] = useState(JSON.parse(gameContext));
    const [animate, setAnimate] = useState(false);

    const { displayText } = useContext(AnimationContext);

    const player = useMemo(() => (userJson ? JSON.parse(userJson) : null), [userJson]);
    const gameUrl = useMemo(() => JSON.parse(document.getElementById('mercure-game-url').textContent), []);

    // No GameEvent subscriber publishes to Mercure yet in the new model, so
    // there's no live resync here for now - refresh the page to see an
    // opponent's move. The "game finished -> redirect" flow still works,
    // it's a separate, already-wired publisher (ContinueRoomSubscriber).
    const onGameEvent = useMemo(() => (data) => {
        if ('end' === data.action) {
            displayText(`${data.data.context.winner.username} a gagné`);
            setAnimate(true);

            setTimeout(() => {
                window.location.href = data.data.url;
            }, 3000);
        }
    }, []);

    useMercure(gameUrl, onGameEvent);

    return <div class={`board${animate ? ' bordel': ''}`}>
        <GameContext gameContext={ctx} player={player} roomId={roomId}>
            <Board players={ctx.players.filter((gamePlayer) => player?.id !== gamePlayer.id)}>
                { 'president' === gameMode && <PresidentBoard ctx={ctx} player={player} /> }
                { 'crazy_eights' === gameMode && <CrazyEightsBoard ctx={ctx} player={player} /> }
            </Board>
        </GameContext>
    </div>;
}
