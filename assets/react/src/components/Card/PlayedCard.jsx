import React, { useContext } from 'react';

import './playedCard.css';
import Card from '../Card.js';
import { GameContext } from '../../Context/GameContext.js';

export default ({ cards }) => {
    const { getCardAsset } = useContext(GameContext);

    return <div className='played_card'>
        {cards.map((card, i) =>
            <Card
                key={i}
                clickable={false}
                card={card}
                img={getCardAsset(card)}
            />
        )}
    </div>;
}
