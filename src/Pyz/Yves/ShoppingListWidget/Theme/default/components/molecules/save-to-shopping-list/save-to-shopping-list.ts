import Component from 'ShopUi/models/component';

export default class SaveToShoppingList extends Component {
    protected static readonly OFFER_RADIO_SELECTOR = 'input[name="product_offer_reference"]:checked';
    protected static readonly OFFER_INPUT_SELECTOR = '.js-shopping-list__form input[name="productOfferReference"]';

    protected readyCallback(): void {}

    protected init(): void {
        const trigger = this.querySelector<HTMLElement>(`[data-qa="save-to-shopping-list-trigger"]`);

        if (!trigger) {
            return;
        }

        trigger.addEventListener('click', () => this.syncOfferReference(), true);
    }

    protected syncOfferReference(): void {
        const checkedOfferRadio = document.querySelector<HTMLInputElement>(SaveToShoppingList.OFFER_RADIO_SELECTOR);

        if (!checkedOfferRadio) {
            return;
        }

        const offerInputs = Array.from(
            document.querySelectorAll<HTMLInputElement>(SaveToShoppingList.OFFER_INPUT_SELECTOR),
        );

        offerInputs.forEach((offerInput) => (offerInput.value = checkedOfferRadio.value));
    }
}
