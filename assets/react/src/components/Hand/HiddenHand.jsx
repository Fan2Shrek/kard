import React, { useCallback, useEffect, useRef, useContext } from 'react';
import { useSprings, animated } from '@react-spring/web';

import Card from '../Card.js';
import { GameContext } from '../../Context/GameContext.js';
import { AnimationContext } from '../../Context/AnimationContext.js';

// To be refactored later, a clean up
// will not be refuse like in my room
export default ({ count, id = null }) => {
    const spread = 60;
    const radius = 180;
    const startAngle = -spread / 1.5;

    const { getBackAsset } = useContext(GameContext);
    const cards = Array.from({ length: count });

    const ref = useRef(null);
    const { addHandRef } = useContext(AnimationContext);

    useEffect(() => {
        id && ref.current && addHandRef(id, ref);
    }, [ref])

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

    // I would like to thanks ChatGPT for helping me with this function
    const calculateStyle = useCallback(
        (index, count) => {
            const totalAngle = 40;
            const angleStep = count > 1 ? totalAngle / (count - 1) : 0;
            const baseAngle = -totalAngle / 2;

            const angle = baseAngle + index * angleStep;
            const radians = (angle * Math.PI) / 180;

            const radius = 100;

            const xOffset = radius * Math.sin(radians);
            const yOffset = radius * (1 - Math.cos(radians));

            return {
                angle,
                xOffset,
                yOffset,
            };
        },
        [spread, radius, startAngle, count],
    );

    return <div ref={ref} className='hand__container--hidden'>
        <div className="hand hand-hidden">
            {springs.map((style, index) => {
                const { angle, xOffset, yOffset } = calculateStyle(index, count);

                return <animated.div key={index}
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
                            angle={angle}
                            xOffset={xOffset}
                            yOffset={yOffset}
                        />
                    </animated.div>
            })}
        </div>
    </div>;
}
