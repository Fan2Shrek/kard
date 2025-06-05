import React, { createContext, useContext, useState, useRef } from 'react';
import { useSpring, animated } from '@react-spring/web';

import Card from '../components/Card.js';
import { GameContext } from './GameContext.js';

export const AnimationContext = createContext({
    animateCard: () => {},
});

export const useAnimation = () => useContext(AnimationContext);

export function AnimationProvider({ children }) {
    const [flyingCard, setFlyingCard] = useState(null);
    const [animationProps, setAnimationProps] = useState(null);
    const [isAnimating, setIsAnimating] = useState(false);

    const { getCardAsset } = useContext(GameContext);

    const animationDoneCallback = useRef(null);

    // Ca semble marcher par miracle
    const animateCard = (card, fromRect, toRect, onDone) => {
        return;
        setIsAnimating(true);
        setFlyingCard(card);
        animationDoneCallback.current = () => {
            if (onDone) {
                onDone();
            }
            setIsAnimating(false);
        };

        if (isAnimating) {
            return;
        }

        setAnimationProps({
            from: { left: fromRect.left, top: fromRect.top },
            to: { left: toRect.left, top: toRect.top },
        });
    };

    const spring = useSpring({
        from: animationProps?.from || {},
        to: animationProps?.to || {},
        config: { tension: 180, friction: 22 },
        onRest: () => {
            if (animationDoneCallback.current) {
                animationDoneCallback.current();
                animationDoneCallback.current = null;
            }
            setFlyingCard(null);
        },
    });

    return (
    <AnimationContext.Provider value={{ animateCard }}>
        {children}
        {flyingCard && animationProps && (
            <animated.div
            style={{
            position: 'absolute',
            zIndex: 1000,
            width: 100,
            height: 150,
            ...spring,
        }}
        >
            <Card card={flyingCard} img={getCardAsset(flyingCard)}/>
        </animated.div>
        )}
    </AnimationContext.Provider>
    );
}

export default AnimationProvider;
