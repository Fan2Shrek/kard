export default class Game {
    constructor(client) {
        this.client = client;
    }

    async play(game, card) {
        return this.client.post(`/api/${game}/play`, card);
    }
}
