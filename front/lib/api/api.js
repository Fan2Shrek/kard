import { API_URL } from "../env";
import UserResource from "./resources/user";

class Api {
    constructor(baseUrl) {
        this.baseUrl = baseUrl;

        this.user = new UserResource(this);
    }

    user() {
        return this.user;
    }

    async get(url) {
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
