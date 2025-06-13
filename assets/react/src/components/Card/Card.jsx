import React, { useRef, useEffect, useState } from 'react';
import { useSpring, animated } from '@react-spring/web'

import './card.css';

export default ({ card, img, springStyle = {}, selected = false, clickable = true, onClick = () => '', angle = null, xOffset = null, yOffset = null }) => {
    const [toggle, setToggle] = useState(selected);

    const styles = useSpring({
        transform: toggle ? 'translate(0, 0)' : 'translate(0, 100px)',
        config: { tension: 170, friction: 26 },
    });

    const containerRef = useRef(null);
    const handleClick = () => {
        if (!clickable) {
            return;
        }

        setToggle(!toggle);
        onClick(card);
    }

    useEffect(() => {
        setToggle(selected);
    }, [selected]);

    const combinedStyle = {
        ...styles,
    };

    const customCss = angle !== null || xOffset !== null || yOffset !== null;

    useEffect(() => {
        if (customCss && containerRef.current && !clickable && !toggle) {
            containerRef.current.style.transform = `rotate(${angle ?? 0}deg) translate(${xOffset ?? 0}px, ${yOffset ?? 0}px)`;
        }
    }, [angle]);

    return <animated.div onClick={handleClick} style={combinedStyle} ref={containerRef} className='card'>
        <img src={img} />
    </animated.div>;
}
