import React, { useContext, useState } from 'react';

import './hand.css';
import Card from '../Card.js';
import api from '../../lib/api.js';
import { GameContext } from '../../Context/GameContext.js';

// @todo handle multiple cards
export default ({ hand, canPlay }) => {
    const { getCardAsset, roomId, currentPlayer } = useContext(GameContext);
    const [selectedCards, setSelectedCards] = useState([]);

    const handleCard = (card) => {
        if (selectedCards.includes(card)) {
            setSelectedCards(selectedCards.filter(c => c !== card));
            return;
        }
        setSelectedCards([...selectedCards, card]);
    }

    const handlePlay = () => {
        api.game.play(roomId, { cards: selectedCards, player: currentPlayer });
        // @todo uncomment
        setSelectedCards([]);
    }

    return <div className='hand__container'>
        {selectedCards.length > 0 && canPlay && <a class="btn" onClick={handlePlay}>Jouer</a>}
        <div className='hand'>
            {hand.map((card, index) => {
                return <Card onClick={handleCard} key={index} card={card} img={getCardAsset(card)} angle={0} />
            })}
        </div>
    </div>;
}
