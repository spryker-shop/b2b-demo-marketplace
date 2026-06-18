import Component from 'ShopUi/models/component';

export default class AgentControl extends Component {
    protected popoverEl: HTMLElement;
    protected pendingForm: HTMLFormElement | null = null;

    protected readyCallback(): void {}

    protected init(): void {
        this.popoverEl = this.querySelector<HTMLElement>(`.${this.jsName}__popover`);

        if (!this.popoverEl) {
            return;
        }

        this.mapEvents();
    }

    protected mapEvents(): void {
        this.popoverEl.addEventListener('mousedown', (event: MouseEvent) => this.onSuggestionMousedown(event));
        this.popoverEl.addEventListener('click', (event: MouseEvent) => this.onPopoverClick(event));
        this.popoverEl.addEventListener('showOverlay', (event: Event) => event.stopPropagation());
        this.popoverEl.addEventListener('hideOverlay', (event: Event) => event.stopPropagation());
        this.addEventListener('mouseleave', () => this.resetSearch());
        this.addEventListener('focusout', (event: FocusEvent) => this.onFocusOut(event));
        document.addEventListener('click', (event: MouseEvent) => this.onConfirmClick(event));
        document.addEventListener('click', (event: MouseEvent) => this.onCancelClick(event));
    }

    protected onSuggestionMousedown(event: MouseEvent): void {
        const target = event.target as HTMLElement;
        if (target.closest('.js-customer-list__container-item')) {
            event.preventDefault();
        }
    }

    protected onPopoverClick(event: MouseEvent): void {
        const target = event.target as HTMLElement;
        const customerItem = target.closest<HTMLElement>('.js-customer-list__container-item');
        if (!customerItem) {
            return;
        }
        const form = this.popoverEl.querySelector<HTMLFormElement>('form');
        if (!form) {
            return;
        }

        const label = customerItem.getAttribute('data-label') || customerItem.textContent || '';
        const cleanLabel = label.replace(/\s+/g, ' ').trim();
        const inputEl = form.querySelector<HTMLInputElement>('.js-autocomplete-form__input');

        if (inputEl) {
            inputEl.value = cleanLabel;
        }

        setTimeout(() => {
            const hidden = form.querySelector<HTMLInputElement>('input[name="_switch_user"]');
            if (!hidden || !hidden.value) {
                return;
            }
            const switchTrigger = this.querySelector<HTMLButtonElement>(`.${this.jsName}__switch-customer-trigger`);
            if (switchTrigger) {
                this.pendingForm = form;
                this.populateSwitchPopupName(cleanLabel);
                switchTrigger.click();
                return;
            }
            form.submit();
        }, 0);
    }

    protected populateSwitchPopupName(newCustomerLabel: string): void {
        document.querySelectorAll<HTMLElement>(`.${this.jsName}__switch-customer-new-name`).forEach((placeholder) => {
            placeholder.textContent = newCustomerLabel;
        });
    }

    protected onConfirmClick(event: MouseEvent): void {
        const target = event.target as HTMLElement;
        const confirmBtn = target.closest(`.${this.jsName}__switch-confirm-trigger`);
        if (!confirmBtn || !this.pendingForm) {
            return;
        }
        this.pendingForm.submit();
    }

    protected onCancelClick(event: MouseEvent): void {
        if (!this.pendingForm) {
            return;
        }

        const target = event.target as HTMLElement;

        if (target.closest(`.${this.jsName}__switch-confirm-trigger`)) {
            return;
        }

        if (!target.closest('.js-main-popup-close')) {
            return;
        }

        this.pendingForm = null;
        this.resetSearch();
    }

    protected onFocusOut(event: FocusEvent): void {
        const relatedTarget = event.relatedTarget as HTMLElement | null;
        if (relatedTarget && this.contains(relatedTarget)) {
            return;
        }
        this.resetSearch();
    }

    protected resetSearch(): void {
        if (this.pendingForm) {
            return;
        }

        const input = this.popoverEl.querySelector<HTMLInputElement>('.js-autocomplete-form__input');
        const hidden = this.popoverEl.querySelector<HTMLInputElement>('.js-autocomplete-form__input-hidden');
        const suggestions = this.popoverEl.querySelector<HTMLElement>('.js-autocomplete-form__container');

        if (input) input.value = '';
        if (hidden) hidden.value = '';
        if (suggestions) {
            suggestions.classList.add('is-hidden');
            const renderer = suggestions.querySelector('ajax-renderer');
            if (renderer) renderer.innerHTML = '';
        }
    }
}
