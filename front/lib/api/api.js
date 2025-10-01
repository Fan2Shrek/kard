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
        return this.userResource;
    }

    room() {
        return this.roomResource;
    }

    async get(url, filters = {}, options = {}) {
        if (filters && Object.keys(filters).length > 0) {
            const queryString = new URLSearchParams(filters).toString();
            url += `?${queryString}`;
        }

        return this._request("GET", url, null, options);
    }

    async post(url, body, options = {}) {
        return this._request("POST", url, body, options);
    }

    async _request(method, url, body = null, options = {}) {
        const defaultOptions = {
            method,
            credentials: "include",
            headers: {
                "Content-Type": "application/json",
            },
        };

        options = { ...defaultOptions, ...options };

        if (body) {
            options.body = JSON.stringify(body);
        }

        // console.log("API Request:", this.baseUrl + url, options);
        const response = await fetch(this.baseUrl + url, options);

        return await response.json();
    }
}

const api = new Api(API_URL);

export default () => api;
