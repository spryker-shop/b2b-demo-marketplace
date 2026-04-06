import Component from 'ShopUi/models/component';

export default class MiniCartDetail extends Component {
    protected linkEl: HTMLElement;
    protected expandBtn: HTMLElement;

    protected readyCallback(): void {}

    protected init(): void {
        this.linkEl = this.querySelector<HTMLElement>(`.${this.jsName}__link`);
        this.expandBtn = this.querySelector<HTMLElement>(`.${this.jsName}__expand-btn`);

        if (this.expandBtn) {
            this.expandBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.toggle();
            });
        }
    }

    protected toggle(): void {
        this.linkEl.classList.toggle(`${this.name}__link--expanded`);
    }
}
