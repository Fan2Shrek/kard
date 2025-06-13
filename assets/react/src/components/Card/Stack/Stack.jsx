import React, { forwardRef, useContext, useCallback } from 'react';

import './stack.css';
import { Card } from '../../components.js';
import { AssetsContext } from '../../../Context/AssetsContext.js';

export default forwardRef(({ cards, style = 'stack' }, ref) => {
    const { getCardAsset, getBackAsset } = useContext(AssetsContext);

    const params = useCallback((card, i) => {
        if (style === 'drawPile') {
            return {
                clickable: false,
                selected: false,
                img: getBackAsset(),
                xOffset: -i,
            };
        }

        if (style === 'stack') {
            return {
                clickable: false,
                img: getCardAsset(card),
                angle: Math.floor(Math.random() * 181),
                xOffset: Math.floor(Math.random() * 10),
                yOffset: Math.floor(Math.random() * 10),
            };
        }
    }, [style]);

    return <div ref={ref} className={`stack ${style}`}>
        {cards.map((card, i) =>
            <Card
                key={`${card.rank}-${card.suit}`}
                clickable={false}
                card={card}
                {...params(card, i)}
            />
        )}
    </div>;
});
