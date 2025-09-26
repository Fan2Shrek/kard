import { useEffect } from 'react'

export default (topic, callback) => {
    useEffect(() => {
        const eventSource = new EventSource(topic, { withCredentials: true });
        eventSource.onmessage = (event) => {
            const data = JSON.parse(event.data);
            if (callback instanceof Function) {
                callback(data);
                return;
            }

            callback[data.action](data.data);
        };

        return () => {
            eventSource.close();
        };
    }, []);
}
