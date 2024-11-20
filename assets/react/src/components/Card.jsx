import React, { useRef, useEffect, useState } from 'react';
import { useSpring, animated } from '@react-spring/web'

export default ({ card, img, onClick = () => '', angle = null, xOffset = null, yOffset = null }) => {
    const [toggle, setToggle] = useState(false);

    const styles = useSpring({
        transform: toggle ? 'translate(100px, 100px)' : 'translate(0px, 0px)',
        config: { tension: 170, friction: 26 },
    });

    const containerRef = useRef(null);
    const handleClick = () => {
        setToggle(!toggle);
    }

    const customCss = angle || xOffset || yOffset;

    useEffect(() => {
        if (customCss && containerRef.current) {
            containerRef.current.style.transform = `rotate(${angle ?? 0}deg) translate(${xOffset ?? 0}px, ${yOffset ?? 0}px)`;
        }
    }, [angle]);
    // useEffect(() => {
    //     if (angle && containerRef.current) {
    //         containerRef.current.style.transform = `rotate(${angle}deg)`;
    //     }
    // }, [angle]);

    return <animated.div onClick={handleClick} style={styles} ref={containerRef} className='card'>
        <img src={img} />
    </animated.div>;
}
