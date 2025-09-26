import Resource from "./resource.js";

export default class UserResource extends Resource {
    async login(username, password) {
        return await this.post("/api/login", { username, password });
    }
}
