import React, { useMemo, useState } from 'react';

import './game.css';
import Hand from './Hand/index.js';
import useMercure from '../hook/useMercure.js';
import GameContext from '../Context/GameContext.js';
import HiddenHand from './Hand/HiddenHand.js';
import Stack from './Card/Stack.js';
import PlayedCard from './Card/PlayedCard.js';
import PlayerList from './Player/PlayerList.js';
import Text from './Animation/Text.js';

export default ({ gameContext, hand: currentHand, player: user }) => {
    const [ctx, setCtx] = useState(JSON.parse(gameContext));
    const [hand, setHand] = useState(currentHand);
    const [text, setText] = useState(null);

    const player = JSON.parse(user);
    const gameUrl = useMemo(() => JSON.parse(document.getElementById('mercure-game-url').textContent), []);
    const playerUrl = useMemo(() => JSON.parse(document.getElementById('mercure-game-player').textContent), []);

    const gameActions = useMemo(() => ({
        play: (data) => {
            setCtx(data);
        },
        message: (data) => {
            setText(data.text);
        },
    }), []);

    useMercure(gameUrl, gameActions);

    useMercure(playerUrl, (data) => {
        setHand(data.cards);
    });

    return <>
        <GameContext gameContext={ctx} player={player} currentPlayer={ctx.currentPlayer}>
            { text && <Text text={text} /> }
            <div className='game'>
                <PlayerList players={ctx.players} currentPlayer={ctx.currentPlayer} />
                <div className='game__right'>
                    <HiddenHand count={5} />
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
        </GameContext>
    </>;
}
