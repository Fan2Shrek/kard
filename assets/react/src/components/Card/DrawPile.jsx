import React, { useContext } from 'react';

import './stack.css';
import Card from '../Card.js';
import { GameContext } from '../../Context/GameContext.js';

export default ({ cards }) => {
    const { getBackAsset } = useContext(GameContext);
    console.log('cards', cards);

    return <div className='stack'>
        {cards.map((card, i) =>
            <Card
                key={`${card.rank}-${card.suit}`}
                clickable={false}
                selected={false}
                card={card}
                xOffset={-i}
                img={getBackAsset()}
            />
        )}
    </div>;
}
