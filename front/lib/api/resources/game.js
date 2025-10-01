import Resource from "./resource";

export class RoomResource extends Resource {
    async getAll(filters = {}) {
        return await this.get("/api/rooms", filters, { cache: "no-store" });
    }
}
