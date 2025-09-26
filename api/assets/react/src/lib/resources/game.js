export default class Game {
    constructor(client) {
        this.client = client;
    }

    async play(game, body) {
        return this.client.post(`/api/game/${game}/play`, body, false);
    }
}
