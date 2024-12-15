import React from 'react';

import './playerList.css';

export default ({ players, currentPlayer }) => {
    return <div className='left player-list'>
        {players.map((player) =>
            <div className={`player-card ${player.id === currentPlayer.id ? 'current' : ''}`} key={player.id || player.username}>
                <span className='player-name'>{player.username}</span>
            </div>
        )}
    </div>
};
