import React, { useContext, useState } from 'react';

import './hand.css';
import Card from '../Card.js';
import api from '../../lib/api.js';
import { GameContext } from '../../Context/GameContext.js';

// @todo handle multiple cards
export default ({ hand }) => {
    const { getCardAsset, gameContext, currentPlayer } = useContext(GameContext);
    const [selectedCards, setSelectedCards] = useState([]);
    const [hasPlayed, setHasPlayed] = useState(false);

    const handleCard = (card) => {
        if (hasPlayed) return;

        if (selectedCards.includes(card)) {
            setSelectedCards(selectedCards.filter(c => c !== card));
            return;
        }
        setSelectedCards([...selectedCards, card]);
    }

    const handlePlay = () => {
        api.game.play(gameContext.room.id, { cards: selectedCards, player: currentPlayer });
        // @todo uncomment
        setHasPlayed(true);
        setSelectedCards([]);
    }

    return <div className='hand__container'>
        {selectedCards.length > 0 && <a class="btn" onClick={handlePlay}>Jouer</a>}
        <div className='hand'>
            {hand.map((card, index) => {
                return <Card onClick={handleCard} key={index} card={card} img={getCardAsset(card)} angle={0} />
            })}
        </div>
    </div>;
}

