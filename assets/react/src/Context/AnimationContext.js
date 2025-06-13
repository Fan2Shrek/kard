import React, { createContext, useContext, useState, useRef } from 'react';
import { useSpring, animated } from '@react-spring/web';

import { AssetsContext } from './AssetsContext.js';
import { Text, Card } from '../components/components.js';

export const AnimationContext = createContext({
    animateCards: () => {},
    getHandRef: (id) => null,
    addHandRef: (id, ref) => null,
    displayText: (text) => {},
});

export const useAnimation = () => useContext(AnimationContext);

export function AnimationProvider({ children }) {
    const [flyingCards, setFlyingCards] = useState(null);
    const [animationProps, setAnimationProps] = useState(null);
    const [isAnimating, setIsAnimating] = useState(false);
    const [animKey, setAnimKey] = useState(0);
    const [text, setText] = useState(null);
    const [key, setKey] = useState(0);

    const [idk, setIdk] = useState([]);
    const [handRefs, setHandRefs] = useState({});

    const { getCardAsset } = useContext(AssetsContext);
    const animationDoneCallback = useRef(null);

    const getHandRef = (id) => {
        if (handRefs[id]) {
            return handRefs[id];
        }
    }

    const addHandRef = (id, ref) => {
        if (!ref.current) {
            console.warn('No ref provided for hand', id);
            return;
        }

        setHandRefs((prev) => ({
            ...prev,
            [id]: ref,
        }));
    };

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

    const displayText = (text) => {
        setKey((prevKey) => prevKey + 1);
        setText(text);
    }

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

    return (
        <AnimationContext.Provider value={{
            animateCards,
            getHandRef,
            addHandRef,
            displayText,
        }}>
            {text && <Text key={key} text={text} />}
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
