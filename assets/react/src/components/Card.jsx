import React, { useRef, useEffect } from 'react';

export default ({ card, img, onClick = () => '', angle = null }) => {
    const containerRef = useRef(null);

    useEffect(() => {
        if (angle && containerRef.current) {
            containerRef.current.style.transform = `rotate(${angle}deg)`;
        }
    }, [angle]);

    return <div ref={containerRef} className='card' onClick={(e) => onClick(card)}>
        <img src={ img } />
    </div>;
}

