import { Controller } from '@hotwired/stimulus';
import { connectStreamSource, disconnectStreamSource } from '@hotwired/turbo';

export default class extends Controller {
    static values = {
        url: String,
    }

    connect() {
        if (this.urlValue) {
            this.es = new EventSource(this.urlValue, { withCredentials: true });
            connectStreamSource(this.es);
        }
    }
    disconnect() {
        if (this.es) {
            this.es.close();
            disconnectStreamSource(this.es);
        }
    }
}

