import React from 'react';

import './playerChoicePlaceHolder.css';

export default ({ player, card }) => {
    return <div className='placeholder'>
        <p>{player.username}</p>
    </div>;
}

