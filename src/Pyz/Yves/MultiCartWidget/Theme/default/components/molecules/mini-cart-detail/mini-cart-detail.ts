import Component from 'ShopUi/models/component';

export default class MiniCartDetail extends Component {
    protected linkEl: HTMLElement;
    protected expandBtn: HTMLElement;
    protected activateForm: HTMLFormElement;

    protected readyCallback(): void {}

    protected init(): void {
        this.linkEl = this.querySelector<HTMLElement>(`.${this.jsName}__link`);
        this.expandBtn = this.querySelector<HTMLElement>(`.${this.jsName}__expand-btn`);
        this.activateForm = this.querySelector<HTMLFormElement>(`.${this.jsName}__activate-form`);

        if (this.activateForm && this.linkEl) {
            this.linkEl.addEventListener('click', (e) => {
                e.preventDefault();
                this.activateForm.submit();
            });
        }

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
