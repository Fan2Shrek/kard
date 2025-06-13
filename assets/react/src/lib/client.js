export default class {
    async post(url, data, throwOnError = true) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            });

            if (!response.ok && throwOnError) {
                throw new Error(response.statusText);
            }

            return response;
        } catch (e) {
            console.error('Error during POST request:', e);
            throw e;
        };
    }
}
