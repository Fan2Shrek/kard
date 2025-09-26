import Resource from './resource.js';

export default class UserResource extends Resource {
	constructor(client) {
		super(client);
	}

	async login(username, password) {
		return await this.post('/api/login', { username, password });
	}
}
