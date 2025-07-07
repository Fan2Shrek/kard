import React, { forwardRef, useContext, useEffect, useState } from 'react';

import { Card, SortButton } from '../components.js';
import { GameContext } from '../../Context/GameContext.js';
import { AssetsContext } from '../../Context/AssetsContext.js';
import api from '../../lib/api.js';

import './hand.css';

export default forwardRef(({ hand, canPlay, order=null, gameActions = null }, ref) => {
    const { roomId, currentPlayer } = useContext(GameContext);
    const { getCardAsset } = useContext(AssetsContext);
    const [selectedCards, setSelectedCards] = useState([]);
    const [cards, setCards] = useState(hand);
    const [error, setError] = useState(null);

	useEffect(() => {
		setCards(cards => cards.filter((card) => hand.some(c => c.rank === card.rank && c.suit === card.suit)));
	}, [hand]);

    const handleCard = (card) => {
        if (selectedCards.includes(card)) {
            setSelectedCards(selectedCards.filter(c => c !== card));
            return;
        }
        setSelectedCards([...selectedCards, card]);
    }

    const handlePlay = async (data = {}) => {
        setError(null);
        const response = await api.game.play(roomId, { cards: selectedCards, player: currentPlayer, data });

        if (!response.ok) {
            const errorData = await response.json();
            setError(errorData.error);
        }

        setSelectedCards([]);
    }

    const actions = gameActions && gameActions(handlePlay) || {};

    return <div ref={ref} className='hand__container'>
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
        {error && <div className='error'>{error}</div>}
        <div className='hand'>
            {cards.map((card) => {
                return <Card onClick={handleCard} selected={selectedCards.includes(card)} key={`${card.rank}-${card.suit}`} card={card} img={getCardAsset(card)} angle={0} />
            })}
        </div>
        { order && <SortButton setCallback={setCards} rankOrder={order} />}
        { !order && <SortButton setCallback={setCards} />}
    </div>;
});
