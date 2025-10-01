export default class Resource {
    constructor(client) {
        this.client = client;
    }

    async get(url, filters = {}, options = {}) {
        return await this.client.get(url, filters, options);
    }

    async post(url, body, options = {}) {
        return await this.client.post(url, body, options);
    }
}
