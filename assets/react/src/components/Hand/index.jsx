import React, { useContext, useState } from 'react';

import './hand.css';
import Card from '../Card.js';
import api from '../../lib/api.js';
import { GameContext } from '../../Context/GameContext.js';

// @todo handle multiple cards
export default ({ hand, canPlay, gameActions = null }) => {
    const { getCardAsset, roomId, currentPlayer } = useContext(GameContext);
    const [selectedCards, setSelectedCards] = useState([]);

    const handleCard = (card) => {
        if (selectedCards.includes(card)) {
            setSelectedCards(selectedCards.filter(c => c !== card));
            return;
        }
        setSelectedCards([...selectedCards, card]);
    }

    const handlePlay = (data = {}) => {
        api.game.play(roomId, { cards: selectedCards, player: currentPlayer, data });

        // @todo uncomment
        setSelectedCards([]);
    }

    const actions = gameActions && gameActions(handlePlay) || {};

    return <div className='hand__container'>
        {selectedCards.length > 0 && canPlay && <a class="button button--medium" onClick={() => handlePlay()}>Jouer</a>}
        {selectedCards.length === 0 && canPlay && <a class="button button--medium" onClick={() => handlePlay()}>Passer</a>}
        {canPlay &&
          Object.entries(actions)
            .filter(([key]) =>
              selectedCards.some(card => card.rank === key)
            )
            .map(([key, element]) => (
                <React.Fragment key={key}>{element}</React.Fragment>
            ))}
        <div className='hand'>
            {hand.map((card) => {
                return <Card onClick={handleCard} selected={selectedCards.includes(card)} key={`${card.rank}-${card.suit}`} card={card} img={getCardAsset(card)} angle={0} />
            })}
        </div>
    </div>;
}
