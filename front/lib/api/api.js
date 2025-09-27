import { API_URL } from "../env";
import { RoomResource } from "./resources/game";
import UserResource from "./resources/user";

class Api {
    constructor(baseUrl) {
        this.baseUrl = baseUrl;

        this.userResource = new UserResource(this);
        this.roomResource = new RoomResource(this);
    }

    user() {
        return this.roomResource;
    }

    room() {
        return this.roomResource;
    }

    async get(url, filters = {}) {
        if (filters && Object.keys(filters).length > 0) {
            const queryString = new URLSearchParams(filters).toString();
            url += `?${queryString}`;
        }

        return this._request("GET", url);
    }

    async post(url, body) {
        return this._request("POST", url, body);
    }

    async _request(method, url, body = null) {
        const options = {
            method,
            credentials: "include",
            headers: {
                "Content-Type": "application/json",
            },
            cache: "no-store",
        };

        if (body) {
            options.body = JSON.stringify(body);
        }

        const response = await fetch(this.baseUrl + url, options);

        return await response.json();
    }
}

const api = new Api(API_URL);

export default () => api;
