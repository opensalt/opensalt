import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['hideable'];

    connect() {
    }

    toggle() {
        this.hideableTargets.map(el => el.classList.toggle('d-none'));
    }
}
