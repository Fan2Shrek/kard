import React from 'react';

import HiddenHand from '../Hand/HiddenHand.js';
import './board.css';

export default ({ children, players }) => {
     const positions = ['top', 'left', 'right'];

    return <div className="board">
        <div className="board__players">
            {players.map((player, index) => {
                const position = positions[index];

                return (
                    <div key={player.id} className={`player-hand player-hand--${position}`}>
                        <HiddenHand count={player.cardsCount} id={player.id} />
                    </div>
                );
            })}
        </div>
        {children}
    </div>;
}
