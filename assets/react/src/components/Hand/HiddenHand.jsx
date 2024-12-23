import React, { useContext } from 'react';

import Card from '../Card.js';
import { GameContext } from '../../Context/GameContext.js';

export default ({ count }) => {
    const { getBackAsset } = useContext(GameContext);
    const cards = [];

    for (let i = 0; i < count; i++) {
        cards.push({ rank: 'A', suit: 'S' });
    }

    return <div className='hand__container'>
        <div className='hand'>
            {cards.map((card, index) => {
                return <Card key={index} clickable={false} card={card} img={getBackAsset()} angle={180}/>
            })}
        </div>
    </div>;
}
