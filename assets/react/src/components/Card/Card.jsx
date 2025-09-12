import React, { useEffect, useState, useRef } from 'react';
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
    yOffset = 0
}) => {
    const [toggle, setToggle] = useState(selected);

    const { transform } = useSpring({
        transform: `
            rotate(${angle}deg)
            translate(${xOffset}px, ${yOffset + (toggle ? -75 : 0)}px)
        `,
        config: { tension: 170, friction: 26 }
    });

    const handleClick = () => {
        if (!clickable) return;

        setToggle(!toggle);
        onClick(card);
    };

    useEffect(() => {
        setToggle(selected);
    }, [selected]);

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
