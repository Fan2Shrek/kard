import Resource from "./resource";

export default class GameModeResource extends Resource {
    async getAll() {
        return await this.get(
            "/api/game_modes",
            {},
            { next: { revalidate: 60 * 60 * 24 } },
        );
    }
}
