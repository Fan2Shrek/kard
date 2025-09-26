import React, { useState, useCallback } from 'react';

import {sortByRank, sortBySuit} from '../../lib/sort.js';

import './sortButton.css';

export default ({ setCallback, setCurrentSort, rankOrder = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'j', 'q', 'k', '1'] }) => {
    const [isOpen, setIsOpen] = useState(false);

    return <div className="sort-button">
        <button className="sort-button__toggle" onClick={() => setIsOpen(!isOpen)}>
            <div className="bar bar--1"></div>
            <div className="bar bar--2"></div>
            <div className="bar bar--3"></div>
        </button>
        {isOpen &&
            <div className="sort-button__menu">
                <button onClick={() => {
                    setCallback(cards => sortByRank(cards, rankOrder));
					setCurrentSort(() => (cards) => sortByRank(cards, rankOrder))
                    setIsOpen(false);
                }}>Trier par valeur</button>
                <button onClick={() => {
                    setCallback(cards => sortBySuit(cards));
					setCurrentSort(() => (cards) => sortBySuit(cards))
                    setIsOpen(false);
                }}>Trier par couleur</button>
            </div>
        }
    </div> };
