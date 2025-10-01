import Resource from "./resource.js";

export default class UserResource extends Resource {
    async login(username, password) {
        return await this.post("/api/login", { username, password });
    }

    async getLeaderboard() {
        return await this.get("/api/leaderboard", null, {
            next: { revalidate: 60 * 60 },
        });
    }
}
