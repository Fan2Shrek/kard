import React, { useState, useCallback } from 'react';

import './sortButton.css';

export default ({ setCallback, rankOrder = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'j', 'q', 'k', '1'] }) => {
    const [isOpen, setIsOpen] = useState(false);

    const sortByRank = useCallback((a, b) => {
        const rankA = rankOrder.indexOf(a.rank);
        const rankB = rankOrder.indexOf(b.rank);

        return rankA - rankB;
    }, []);

    const sortBySuit = useCallback((a, b) => a.suit.localeCompare(b.suit), []);

    return <div className="sort-button">
        <button className="sort-button__toggle" onClick={() => setIsOpen(!isOpen)}>
            <div className="bar bar--1"></div>
            <div className="bar bar--2"></div>
            <div className="bar bar--3"></div>
        </button>
        {isOpen &&
            <div className="sort-button__menu">
                <button onClick={() => {
                    setCallback(cards => [...cards].sort(sortByRank));
                    setIsOpen(false);
                }}>Trier par valeur</button>
                <button onClick={() => {
                    setCallback(cards => [...cards].sort(sortBySuit));
                    setIsOpen(false);
                }}>Trier par couleur</button>
            </div>
        }
    </div> };
