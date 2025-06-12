import React, { useContext } from 'react';

import './stack.css';
import Card from '../Card.js';
import { AssetsContext } from '../../Context/AssetsContext.js';

export default ({ cards }) => {
    const { getBackAsset } = useContext(AssetsContext);

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
