import React ,{ useEffect } from 'react'

export default (topic, callback) => {
    useEffect(() => {
        const eventSource = new EventSource(topic);
        eventSource.onmessage = (event) => {
            callback(JSON.parse(event.data));
        };

        return () => {
            eventSource.close();
        };
    }, [topic, callback]);
}
