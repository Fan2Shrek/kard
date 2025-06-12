import React, { forwardRef, useContext } from 'react';

import './stack.css';
import Card from '../Card.js';
import { AssetsContext } from '../../Context/AssetsContext.js';

export default forwardRef(({ cards }, ref) => {
    const { getCardAsset } = useContext(AssetsContext);

    return <div ref={ref} className='stack'>
        {cards.map((card, i) =>
            <Card
                key={i}
                clickable={false}
                card={card}
                img={getCardAsset(card)}
                angle={Math.floor(Math.random() * 181)}
                xOffset={Math.floor(Math.random() * 10)}
                yOffset={Math.floor(Math.random() * 10)}
            />
        )}
    </div>;
});
