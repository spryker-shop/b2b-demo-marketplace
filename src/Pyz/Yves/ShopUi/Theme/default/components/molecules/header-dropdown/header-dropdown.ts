import Component from 'ShopUi/models/component';

export default class HeaderDropdown extends Component {
    protected trigger: HTMLElement;
    protected dropdown: HTMLElement;

    protected readyCallback(): void {}

    protected init(): void {
        this.trigger = this.querySelector<HTMLElement>(`.${this.jsName}__trigger`);
        this.dropdown = this.querySelector<HTMLElement>(`.${this.jsName}__dropdown`);

        if (!this.trigger || !this.dropdown) {
            return;
        }

        this.mapEvents();
    }

    protected mapEvents(): void {
        this.addEventListener('mouseenter', () => this.syncAria(true));
        this.addEventListener('mouseleave', () => this.syncAria(false));
        this.addEventListener('focusin', () => this.syncAria(true));
        this.addEventListener('focusout', (event: FocusEvent) => this.onFocusOut(event));
        document.addEventListener('keydown', (event: KeyboardEvent) => this.onKeyDown(event));
    }

    protected onFocusOut(event: FocusEvent): void {
        const relatedTarget = event.relatedTarget as HTMLElement | null;

        if (!relatedTarget || !this.contains(relatedTarget)) {
            this.syncAria(false);
        }
    }

    protected onKeyDown(event: KeyboardEvent): void {
        if (event.key === 'Escape' && this.isOpen) {
            this.syncAria(false);
            (this.trigger as HTMLButtonElement)?.focus();
        }
    }

    protected syncAria(isOpen: boolean): void {
        this.trigger.setAttribute('aria-expanded', String(isOpen));
        this.dropdown.setAttribute('aria-hidden', String(!isOpen));
    }

    protected get isOpen(): boolean {
        return this.trigger.getAttribute('aria-expanded') === 'true';
    }
}
