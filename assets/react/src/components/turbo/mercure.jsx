import React from 'react';

// @todo url in config    
export default ({ topic }) => {
    return <div
        data-controller="symfony--ux-turbo--mercure-turbo-stream"
        data-symfony--ux-turbo--mercure-turbo-stream-topic-value={topic}
        data-symfony--ux-turbo--mercure-turbo-stream-hub-value="http://localhost:8090/.well-known/mercure"
    >
    </div>;
}
