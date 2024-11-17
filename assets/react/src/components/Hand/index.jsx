import React, { useContext, useState } from 'react';

import './hand.css';
import Card from '../Card.js';
import api from '../../lib/api.js';
import { GameContext } from '../../Context/GameContext.js';

export default ({ hand }) => {
    const { getCardAsset, gameContext, currentPlayer } = useContext(GameContext);
    const [hasPlayed, setHasPlayed] = useState(false);

    const handleCard = (card) => {
        if (hasPlayed) return;
        api.game.play(gameContext.room.id, { card, player: currentPlayer });
        setHasPlayed(true);
    }

    return <div className='hand__container'>
        <div className='hand'>
            {hand.map((card, index) => {
                return <Card onClick={handleCard} key={index} card={card} img={getCardAsset(card)} angle={0}/>
            })}
        </div>
    </div>;
}

