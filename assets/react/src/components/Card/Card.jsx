import React from 'react';
import { useSpring, animated } from '@react-spring/web';

import './card.css';

export default ({
    card,
    img,
    selected = false,
    clickable = true,
    onClick = () => {},
    angle = 0,
    xOffset = 0,
    yOffset = 0,
    lift = 75
}) => {
    // The lift follows the `selected` prop alone. It used to also toggle on its
    // own on every click, which lifted cards the parent had not selected - the
    // card you clicked to drop onto, for instance.
    const { transform } = useSpring({
        transform: `
            rotate(${angle}deg)
            translate(${xOffset}px, ${yOffset + (selected ? -lift : 0)}px)
        `,
        config: { tension: 170, friction: 26 }
    });

    const handleClick = () => {
        if (!clickable) return;

        onClick(card);
    };

    return (
        <animated.div
            onClick={handleClick}
            style={{ transform }}
            className='card'
        >
            <img src={img} />
        </animated.div>
    );
}
