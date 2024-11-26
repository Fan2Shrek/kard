import React, { useRef, useEffect } from 'react';

export default ({ card, img, onClick = () => '', angle = null, xOffset = null, yOffset = null }) => {
    const containerRef = useRef(null);

    const customCss = angle || xOffset || yOffset;
        
    useEffect(() => {
        if (customCss && containerRef.current) {
            containerRef.current.style.transform = `rotate(${angle ?? 0}deg) translate(${xOffset ?? 0}px, ${yOffset ?? 0}px)`;
        }
    }, [angle]);

    return <div ref={containerRef} className='card' onClick={(e) => onClick(card)}>
        <img src={ img } />
    </div>;
}

