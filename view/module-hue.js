import {Widget} from "/js/widget.js";

customElements.define('module-hue', class extends Widget {
    static service = 'service-module-hue';

    static render(data) {
        return Widget.parse`...`;
    }
});