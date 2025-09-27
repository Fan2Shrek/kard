export default class Resource {
    constructor(client) {
        this.client = client;
    }

    async get(url, filters = {}) {
        return await this.client.get(url, filters);
    }

    async post(url, body) {
        return await this.client.post(url, body);
    }
}
