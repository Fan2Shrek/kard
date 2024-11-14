import React, { useContext } from 'react';

import './hand.module.css';
import Card from '../Card.js';
import { GameContext } from '../../Context/GameContext.js';

export default ({ hand }) => {
    const { getCardAsset } = useContext(GameContext);

    return <div className='hand'>
        {hand.map((card, index) => {
            return <Card key={index} img={getCardAsset(card)} />
        })}
    </div>;
}
