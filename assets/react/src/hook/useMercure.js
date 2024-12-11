import { useEffect } from 'react'

export default (topic, callback) => {
    useEffect(() => {
        const eventSource = new EventSource(topic, { withCredentials: true });
        eventSource.onmessage = (event) => {
            callback(JSON.parse(event.data));
        };

        return () => {
            eventSource.close();
        };
    }, []);
}
