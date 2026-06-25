import Component from 'ShopUi/models/component';

export default class SellerList extends Component {
    protected static readonly INLINE_GROUP = 'product_offer_reference';
    protected static readonly MODAL_GROUP = 'product_offer_reference_modal';
    protected isApplying = false;

    protected init(): void {
        this.mapEvents();
    }

    protected mapEvents(): void {
        const inlineRadios = <HTMLInputElement[]>(
            Array.from(this.querySelectorAll(`input[name="${SellerList.INLINE_GROUP}"]`))
        );
        inlineRadios.forEach((radio) => radio.addEventListener('change', () => this.applyOffer(radio.value)));

        document.addEventListener('click', (event: Event) => this.onDocumentClick(event));
    }

    protected onDocumentClick(event: Event): void {
        const target = <HTMLElement>event.target;

        if (target.closest(`.${this.jsName}__view-all`)) {
            queueMicrotask(() => this.syncModalToInline());

            return;
        }

        const applyTrigger = target.closest(`.${this.jsName}__apply`);

        if (!applyTrigger) {
            return;
        }

        const modalRadio = <HTMLInputElement>document.querySelector(`input[name="${SellerList.MODAL_GROUP}"]:checked`);

        if (modalRadio) {
            this.applyOffer(modalRadio.value);
        }

        this.closePopup(<HTMLElement>applyTrigger);
    }

    protected applyOffer(reference: string): void {
        if (this.isApplying) {
            return;
        }

        this.isApplying = true;
        this.syncRadios(reference);
        this.notifyOfferChange(reference);
        this.updatePrice(reference);
        this.resetQuantities();
        this.updateOfferInputs(reference);
        this.updateLocation(reference);
        this.isApplying = false;
    }

    protected resetQuantities(): void {
        const scope = document.querySelector(this.cartFormSelector) ?? document;
        const inputs = <HTMLInputElement[]>(
            Array.from(scope.querySelectorAll('quantity-counter input:not([type="hidden"])'))
        );

        inputs.forEach((input) => {
            const defaultValue = input.defaultValue || input.getAttribute('min') || '1';

            if (Number(input.value) === Number(defaultValue)) {
                return;
            }

            input.value = defaultValue;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    protected notifyOfferChange(reference: string): void {
        const inlineRadio = <HTMLInputElement>(
            this.querySelector(`input[name="${SellerList.INLINE_GROUP}"][value="${reference}"]`)
        );

        inlineRadio?.dispatchEvent(new Event('change', { bubbles: true }));
    }

    protected syncRadios(reference: string): void {
        const radios = <HTMLInputElement[]>(
            Array.from(
                document.querySelectorAll(
                    `input[name="${SellerList.INLINE_GROUP}"], input[name="${SellerList.MODAL_GROUP}"]`,
                ),
            )
        );

        radios.forEach((radio) => (radio.checked = radio.value === reference));
    }

    protected syncModalToInline(): void {
        const checkedInlineRadio = <HTMLInputElement>(
            this.querySelector(`input[name="${SellerList.INLINE_GROUP}"]:checked`)
        );

        if (checkedInlineRadio) {
            this.syncRadios(checkedInlineRadio.value);
        }
    }

    protected updatePrice(reference: string): void {
        const priceContainers = <HTMLElement[]>Array.from(document.querySelectorAll(`[${this.priceOfferAttribute}]`));

        if (!priceContainers.length) {
            return;
        }

        priceContainers.forEach((container) =>
            container.classList.toggle('is-hidden', container.getAttribute(this.priceOfferAttribute) !== reference),
        );
    }

    protected updateOfferInputs(reference: string): void {
        const offerInputs = <HTMLInputElement[]>(
            Array.from(document.querySelectorAll('input[name="productOfferReference"]'))
        );

        offerInputs.forEach((input) => (input.value = reference));
        this.updateCartFormOfferInput(reference);
    }

    protected updateCartFormOfferInput(reference: string): void {
        const cartForm = <HTMLFormElement>document.querySelector(this.cartFormSelector);

        if (!cartForm) {
            return;
        }

        let offerInput = <HTMLInputElement>cartForm.getElementsByClassName(`${this.jsName}__offer-input`)[0];

        if (!offerInput) {
            offerInput = document.createElement('input');
            offerInput.type = 'hidden';
            offerInput.name = SellerList.INLINE_GROUP;
            offerInput.className = `${this.jsName}__offer-input`;
            cartForm.appendChild(offerInput);
        }

        offerInput.value = reference;
    }

    protected updateLocation(reference: string): void {
        const params = new URLSearchParams(window.location.search);
        params.set('attribute[selected_merchant_reference_type]', SellerList.INLINE_GROUP);
        params.set('attribute[selected_merchant_reference]', reference);
        window.history.replaceState(null, '', `${window.location.pathname}?${params.toString()}`);
    }

    protected closePopup(applyTrigger: HTMLElement): void {
        const popup = applyTrigger.closest('.main-popup') || document.querySelector('.compare-sellers');
        const closeButton = <HTMLElement>popup?.querySelector('.js-main-popup__close');

        closeButton?.click();
    }

    protected get priceOfferAttribute(): string {
        return this.getAttribute('price-offer-attribute') || 'data-product-price-offer';
    }

    protected get cartFormSelector(): string {
        return this.getAttribute('cart-form-selector') || 'form[action*="/cart/add"]';
    }
}
