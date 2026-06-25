import Component from 'ShopUi/models/component';

export default class SaveToShoppingList extends Component {

    protected init(): void {
        document.addEventListener(
            'click',
            (event: Event) => {
                if ((<HTMLElement>event.target).closest(`.${this.jsName}__trigger`)) {
                    this.syncOfferReference();
                }
            },
            true,
        );
    }

    protected syncOfferReference(): void {
        const checkedOfferRadio = document.querySelector<HTMLInputElement>(
            'input[name="product_offer_reference"]:checked',
        );

        if (!checkedOfferRadio) {
            return;
        }

        const offerInputs = Array.from(
            document.querySelectorAll<HTMLInputElement>('.js-shopping-list__form input[name="productOfferReference"]'),
        );

        offerInputs.forEach((offerInput) => (offerInput.value = checkedOfferRadio.value));
    }
}
