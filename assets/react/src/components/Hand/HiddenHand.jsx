import React, { useContext } from 'react';
import { useSprings, animated } from '@react-spring/web';

import Card from '../Card.js';
import { GameContext } from '../../Context/GameContext.js';

// /!\ Important note
// I'm not a mathematician
// This is just cool and random numbers & formulae
export default ({ count }) => {
    const spread = 60;
    const radius = 180;
    const startAngle = -spread / 1.5;

    const { getBackAsset } = useContext(GameContext);
    const cards = Array.from({ length: count });

    const springs = useSprings(
        count,
        cards.map((_, index) => {
            const angle = startAngle + (spread / (count - 1 || 1)) * index;

            return {
                transform: `translateY(-${radius}px) `,
                rotate: angle,
                config: { tension: 250, friction: 30 },
            };
        })
    );

    return <div className='hand__container--hidden'>
        <div className="hand hand-hidden">
            {springs.map((style, index) => (
                <animated.div key={index}
                    style={{
                        ...style,
                        position: 'absolute',
                        transformOrigin: 'bottom center',
                    }}
                >
                    <Card
                        clickable={false}
                        springStyle={{
                            ...style,
                            position: 'absolute',
                            transformOrigin: 'bottom center',
                        }}
                        card={cards[index]}
                        img={getBackAsset()}
                    />
                </animated.div>
            ))}
        </div>
    </div>;
}
