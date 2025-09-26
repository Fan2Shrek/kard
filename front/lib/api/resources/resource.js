export default class Resource {
    constructor(client) {
        this.client = client;
    }

    async get(url) {
        return await this.client.get(url);
    }

    async post(url, body) {
        return await this.client.post(url, body);
    }
}
