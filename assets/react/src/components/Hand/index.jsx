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
        {selectedCards.length > 0 && canPlay && <a class="button button--medium" onClick={handlePlay}>Jouer</a>}
        {selectedCards.length === 0 && canPlay && <a class="button button--medium" onClick={handlePlay}>Passer</a>}
        <div className='hand'>
            {hand.map((card) => {
                return <Card onClick={handleCard} selected={selectedCards.includes(card)} key={`${card.rank}-${card.suit}`} card={card} img={getCardAsset(card)} angle={0} />
            })}
        </div>
    </div>;
}
