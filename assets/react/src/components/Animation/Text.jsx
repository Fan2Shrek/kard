import React, { useState, useEffect } from 'react';
import { useSpring, useTrail, animated } from '@react-spring/web'

import './text.css';

export default ({ text }) => {
    const [visible, setVisible] = useState(true);

    const letters = text.split('');
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
            { opacity: 1, config: { duration: 1000 } },
            { opacity: 0, delay: 5000,config: { duration: 1000 } },
        ],
    });

    useEffect(() => {
        const timer = setTimeout(() => setVisible(false), 7000);
        return () => clearTimeout(timer);
    }, [text]);

    return (
        visible && <animated.div class="animated__text" style={divStyle}>
            {trail.map((style, index) => (
                <animated.span key={index} style={style} class={' ' === letters[index] ? 'space' : ''}>
                    {letters[index]}
                </animated.span>
            ))}
        </animated.div>
    );
}
