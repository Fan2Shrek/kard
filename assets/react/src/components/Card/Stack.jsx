import React, { useContext } from 'react';

import './stack.css';
import Card from '../Card.js';
import { GameContext } from '../../Context/GameContext.js';

export default ({ cards }) => {
    const { getCardAsset } = useContext(GameContext);

    return <div className='stack'>
        {cards.map((card, i) =>
            <Card
                key={i}
                clickable={false}
                card={card}
                img={getCardAsset(card)}
                angle={Math.floor(Math.random() * 181)}
                xOffset={Math.floor(Math.random() * 101)}
                yOffset={Math.floor(Math.random() * 101)}
            />
        )}
    </div>;
}
