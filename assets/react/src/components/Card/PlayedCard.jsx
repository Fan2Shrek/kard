import React, { forwardRef, useContext } from 'react';

import './playedCard.css';
import { Card } from '../components.js';
import { AssetsContext } from '../../Context/AssetsContext.js';

export default forwardRef(({ turns }, ref) => {
    const { getCardAsset } = useContext(AssetsContext);

    if (turns.length === 0) {
        return null;
    }

    const lastTurn = turns[turns.length - 1];
    turns = turns.slice(0, -1);

    const cards = turns.map(t => t.cards).flat();

    return <div className='played_card'>
        {cards.map((card, i) =>
            <Card
                key={i}
                clickable={false}
                card={card}
                img={getCardAsset(card)}
            />
        )}
        {lastTurn &&
            <div ref={ref} className='played_card__last_turn'>
                {lastTurn.cards.map((card, i) =>
                    <Card
                        key={card.suit + card.rank}
                        clickable={false}
                        card={card}
                        img={getCardAsset(card)}
                    />
                )}
            </div>
        }
    </div>;
});
