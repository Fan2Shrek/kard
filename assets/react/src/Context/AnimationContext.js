import React, { createContext, useContext, useState, useRef } from 'react';
import { useSpring, animated } from '@react-spring/web';

import Card from '../components/Card.js';
import { GameContext } from './GameContext.js';

export const AnimationContext = createContext({
    animateCards: () => {},
});

export const useAnimation = () => useContext(AnimationContext);

export function AnimationProvider({ children }) {
    const [flyingCards, setFlyingCards] = useState(null);
    const [animationProps, setAnimationProps] = useState(null);
    const [isAnimating, setIsAnimating] = useState(false);
    const [animKey, setAnimKey] = useState(0);

    const [idk, setIdk] = useState([]);

    const { getCardAsset } = useContext(GameContext);
    const animationDoneCallback = useRef(null);

    const animateCards = (cards, from, to) => {
        const key = cards.reduce((acc, cur) => `${acc}_${cur.suit}--${cur.rank}`, '');
        if (cards.length == 0 || isAnimating || idk.includes(key)) {
            return;
        }

        setIsAnimating(true);
        setFlyingCards(cards);
        to.style.opacity = 0;

        animationDoneCallback.current = () => {
            setFlyingCards(null);
            setAnimationProps(null);
            setIsAnimating(false);
            to.style.opacity = 1;
            setIdk(() => [...idk, key]);
        };

        const fromRect = from.getBoundingClientRect();
        const toRect = to.getBoundingClientRect();

        setAnimationProps({
            from: { left: fromRect.left, top: fromRect.top },
            to: { left: toRect.left, top: toRect.top },
        });
    };

    const spring = useSpring({
        from: animationProps?.from || {},
        to: animationProps?.to || {},
        config: { tension: 180, friction: 22 },
        reset: true,
        onRest: () => {
            if (animationDoneCallback.current) {
                animationDoneCallback.current();
                animationDoneCallback.current = null;
            }
        },
    });

    console.log(spring)

    return (
        <AnimationContext.Provider value={{ animateCards }}>
            {children}
            {flyingCards && animationProps && isAnimating && (
                <animated.div
                    style={{
                        position: 'absolute',
                        display: 'flex',
                        zIndex: 1000,
                        width: 100,
                        height: 150,
                        ...spring,
                    }}
                >
                    {flyingCards.map((card, index) => <Card card={card} img={getCardAsset(card)}/>)}
                </animated.div>
            )}
        </AnimationContext.Provider>
    );
}

export default AnimationProvider;
