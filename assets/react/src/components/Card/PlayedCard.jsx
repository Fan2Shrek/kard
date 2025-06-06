import React, { forwardRef, useContext } from 'react';

import './playedCard.css';
import Card from '../Card.js';
import { GameContext } from '../../Context/GameContext.js';

export default forwardRef(({ cards }, ref) => {
    const { getCardAsset } = useContext(GameContext);

    if (cards.length === 0) {
        return null;
    }

    const lastCard = cards[cards.length - 1];
    cards = cards.slice(0, -1);

    return <div className='played_card'>
        {cards.map((card, i) =>
            <Card
                key={i}
                clickable={false}
                card={card}
                img={getCardAsset(card)}
            />
        )}
        {lastCard &&
            <div ref={ref}>
                <Card
                    clickable={false}
                    card={lastCard}
                    img={getCardAsset(lastCard)}
                />
            </div>
        }
    </div>;
});
