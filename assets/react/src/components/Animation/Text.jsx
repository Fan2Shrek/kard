import React, { useState, useEffect } from 'react';
import { useSpring, useTrail, animated } from '@react-spring/web'

import './text.css';

export default ({ text }) => {
    const [visible, setVisible] = useState(true);
    const [key, setKey] = useState(0);

    const letters = text.split('');
	const fontSize = `clamp(2rem, ${100 / text.length}vw, 10rem)`;

    const trail = useTrail(letters.length, {
        from: { transform: 'translateY(0px)' },
        to: { transform: 'translateY(-40px)' },
        loop: { reverse: true },
        config: { mass: 1, tension: 200, friction: 15 },
        delay: 200,
    });
    const divStyle = useSpring({
        from: { opacity: 0 },
        to: [
            { opacity: 1, config: { duration: 1000 }, fontSize},
            { opacity: 0, delay: 5000,config: { duration: 1000 } },
        ],
    });

    useEffect(() => {
        setVisible(true);
        setKey((key) => key + 1);
        const timer = setTimeout(() => setVisible(false), 7000);
        return () => clearTimeout(timer);
    }, [text]);

    return (
        visible && <animated.div key={key} className="animated__text" style={divStyle}>
            {trail.map((style, index) => (
                <animated.span key={index} style={style} class={' ' === letters[index] ? 'space' : ''}>
                    {letters[index]}
                </animated.span>
            ))}
        </animated.div>
    );
}
