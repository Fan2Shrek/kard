import React, { useMemo, useState, useEffect} from 'react';

import './game.css';
import useMercure from '../hook/useMercure.js';
import GameContext from '../Context/GameContext.js';
import Text from './Animation/Text.js';
import PresidentBoard from './GameMode/PresidentBoard.js';
import CrazyEightsBoard from './GameMode/CrazyEightsBoard.js';
import PlayerList from "./Player/PlayerList";
import PlayedCard from "./Card/PlayedCard";
import Stack from "./Card/Stack";
import HiddenHand from "./Hand/HiddenHand";

export default ({ gameContext, hand: currentHand, player: user}) => {
    const [ctx, setCtx] = useState(JSON.parse(gameContext));
    const [hand, setHand] = useState(currentHand);
    const [text, setText] = useState(null);
    const [key, setKey] = useState(0);

    const player = JSON.parse(user);
    const gameUrl = useMemo(() => JSON.parse(document.getElementById('mercure-game-url').textContent), []);
    const playerUrl = useMemo(() => JSON.parse(document.getElementById('mercure-game-player').textContent), []);

    const gameActions = useMemo(() => ({
        play: (data) => {
            setCtx(data);
        },
        message: (data) => {
            setKey((key) => key + 1);
            setText(data.text);
        },
        end: (data) => {
            setCtx(data.context);
            setText(`${data.context.currentPlayer.username} a gagné`);

            setTimeout(() => {
                window.location.href = data.url;
            }, 3000);
        },
    }), []);

    useMercure(gameUrl, gameActions);

    useMercure(playerUrl, (data) => {
        setHand(data.cards);
    });

    const presidentOrder = [
        '3', '4', '5', '6', '7', '8', '9', '10', 'j', 'q', 'k', '1', '2'
    ];

    function getCardValue(card) {
        return presidentOrder.indexOf(String(card.rank).toLowerCase());
    }

    function sortHand(hand) {
        return [...hand].sort((a, b) => getCardValue(a) - getCardValue(b));
    }

    const otherPlayers = ctx.players.filter(p => p.id !== player.id);

    return <>
        <GameContext gameContext={ctx} player={player} currentPlayer={ctx.currentPlayer}>
            {text && <Text key={key} text={text}/>}
            <div className='game'>
                <PlayerList players={ctx.players} currentPlayer={ctx.currentPlayer}/>
                <div className='game__right'>
                    {otherPlayers.map(p =>
                        <HiddenHand key={p.id} count={p.cardsCount ?? 0}/>
                    )}
                    <div className='middle'>
                        <div id='middle'>
                            <PlayedCard cards={ctx.round.turns.map(t => t.cards).flat()}/>
                            <Stack cards={ctx.discarded}/>
                        </div>
                    </div>
                    <div className='bottom'>
                        <Hand hand={sortHand(hand)} canPlay={ctx.currentPlayer.id === player.id}/>
                    </div>
                </div>
            </div>
            { text && <Text key={key} text={text} /> }
            { ctx.room.gameMode.value === 'president' && <PresidentBoard ctx={ctx} hand={hand} player={player} /> }
            { ctx.room.gameMode.value === 'crazy_eights' && <CrazyEightsBoard ctx={ctx} hand={hand} player={player} /> }
        </GameContext>
    </>;
}
