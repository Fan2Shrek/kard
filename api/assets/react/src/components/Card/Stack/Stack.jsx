import React, { forwardRef, useContext, useCallback, useState } from 'react';

import './stack.css';
import { Card } from '../../components.js';
import { AssetsContext } from '../../../Context/AssetsContext.js';

export default forwardRef(({ cards, style = 'stack' }, ref) => {
    const { getCardAsset, getBackAsset } = useContext(AssetsContext);
	const [styleDict, setStyleDict] = useState({});

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
			if (!styleDict[card.rank + card.suit]) {
				styleDict[card.rank + card.suit] = {
					angle: Math.floor(Math.random() * 181),
					xOffset: Math.floor(Math.random() * 10),
					yOffset: Math.floor(Math.random() * 10),
				};
				setStyleDict({ ...styleDict });
			}
            return {
                clickable: false,
                img: getCardAsset(card),
                angle: styleDict[card.rank + card.suit].angle,
                xOffset: styleDict[card.rank + card.suit].xOffset,
                yOffset: styleDict[card.rank + card.suit].yOffset
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
