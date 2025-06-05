import React, { forwardRef, useContext } from 'react';

import './playedCard.css';
import Card from '../Card.js';
import { GameContext } from '../../Context/GameContext.js';

export default forwardRef(({ cards }, ref) => {
    const { getCardAsset } = useContext(GameContext);

    return <div ref={ref} className='played_card'>
        {cards.map((card, i) =>
            <Card
                key={i}
                clickable={false}
                card={card}
                img={getCardAsset(card)}
            />
        )}
    </div>;
});
