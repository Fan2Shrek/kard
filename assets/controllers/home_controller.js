import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        const url = JSON.parse(document.getElementById("mercure-url").textContent);
        const eventSource = new EventSource(url);
    }
}
