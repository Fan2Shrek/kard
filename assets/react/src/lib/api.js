import Client from './client.js';
import Game from './resources/game.js';

class Api {
    constructor() {
        this.client = new Client();
        this.game = new Game(this.client);
    }
}

const api = new Api();
export default api;
