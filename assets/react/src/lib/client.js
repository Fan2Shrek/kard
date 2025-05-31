export default class {
    async get(url) {
        const response = await fetch(url);

        return response.json();
    }

    async post(url, data) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            });

            if (!response.ok) {
                throw new Error(response.statusText);
            }

            return response.json();
        } catch (e) {
            console.error('Error during POST request:', e);
            throw e;
        };
    }
}
