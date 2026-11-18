import Component from 'ShopUi/models/component';

interface ControlState {
    valid: boolean;
    main: {
        checked?: boolean;
        value?: string;
    };
    items: {
        item: HTMLElement;
        value: string;
    }[];
}

export default class AddressItemFormFieldList extends Component {
    protected visibleWithoutValidation: boolean = null;
    protected controls: Record<string, ControlState> = {};
    protected DEFAULT_VALUE = '0';

    protected sameAddressForAllItemsControl: HTMLElement[];
    protected productItems: HTMLElement[];
    protected elementsToToggle: HTMLElement[];

    protected observer = new MutationObserver(this.onInputChangeCallback.bind(this));

    protected init(): void {
        this.elementsToToggle = Array.from(
            document.querySelectorAll<HTMLElement>(`.${this.getAttribute('elements-to-toggle-class')}`),
        );

        if (document.querySelector(`[address-item-form-drop-validation]`)) {
            this.visibleWithoutValidation = false;

            this.validation();

            return;
        }

        this.sameAddressForAllItemsControl = Array.from(
            this.querySelectorAll<HTMLElement>(`.${this.getAttribute('same-address-for-all-items-control')} input`),
        );

        const excludedTypes: string[] = JSON.parse(this.getAttribute('excluded-types') || '[]');

        this.productItems = Array.from(
            this.querySelectorAll<HTMLElement>(`.${this.getAttribute('product-item')}`),
        ).filter((element) => !excludedTypes.includes(element.getAttribute('shipment-type')));

        this.mapEvents();
    }

    disconnectedCallback() {
        this.observer.disconnect();
    }

    protected mapEvents(): void {
        this.sameAddressForAllItemsControl.forEach((control: HTMLInputElement) => {
            const wrapper = control.closest<HTMLElement>(`.${this.getAttribute('product-item')}`);

            if (!wrapper) {
                return;
            }

            const groupIndex = wrapper.getAttribute('group-index');
            const controlClass = wrapper.getAttribute('address-control');
            const main = {
                checked: control.checked,
                value: this.DEFAULT_VALUE,
            };

            this.controls[groupIndex] = {
                valid: false,
                main: {},
                items: Array.from(
                    this.querySelectorAll<HTMLElement>(
                        `.${this.getAttribute('product-item')}[group-index="${groupIndex}"]`,
                    ),
                )
                    .map((item) => {
                        const addressControl = controlClass
                            ? item.querySelector<HTMLInputElement>(`.${controlClass}`)
                            : null;

                        if (!addressControl) {
                            return null;
                        }

                        const value = addressControl.value;

                        if (item.querySelector(`.${this.getAttribute('same-address-for-all-items-control')}`)) {
                            main.value = value;
                        }

                        return {
                            item,
                            value,
                        };
                    })
                    .filter(Boolean),
            };
            this.controls[groupIndex].main = main;

            control?.addEventListener('change', (event) => {
                this.controls[groupIndex].main.checked = (event.target as HTMLInputElement).checked;
                this.validation();
            });
        });

        const items = Object.values(this.controls).flatMap((data) => {
            data.items.forEach(({ item }) => {
                const addressControl = item.getAttribute('address-control');
                const input = addressControl ? item.querySelector<HTMLInputElement>(`.${addressControl}`) : null;

                if (!input) {
                    return;
                }

                this.observer.observe(input, { attributes: true, attributeFilter: ['value'] });
            });

            return data.items;
        });

        if (items.length === 1 && this.productItems.length === 1) {
            this.visibleWithoutValidation = true;
        }

        this.validation();
    }

    protected onInputChangeCallback(event: MutationRecord[]): void {
        const target = event[0].target as HTMLInputElement;
        const element = target.closest<HTMLElement>(`.${this.getAttribute('product-item')}`);

        if (!element) {
            return;
        }

        const groupIndex = element.getAttribute('group-index');
        const control = this.controls[groupIndex];

        if (!control) {
            return;
        }

        const value = target.value;
        const changedItem = control.items.find((child) => child.item === element);

        if (!changedItem) {
            return;
        }

        changedItem.value = value;

        if (element.querySelector(`.${this.getAttribute('same-address-for-all-items-control')}`)) {
            control.main.value = value;
        }

        this.validation();
    }

    protected validation(): void {
        const isValid = this.isValid();

        this.elementsToToggle.forEach((element) => {
            element.classList.toggle('is-hidden', !isValid);

            const input = element.querySelector<HTMLInputElement>('input');

            if (!isValid && input?.checked) {
                input.checked = false;
                input.dispatchEvent(new Event('change'));
            }
        });
    }

    protected isValid(): boolean {
        const valuesToCompare: string[] = [];

        if (this.visibleWithoutValidation !== null) {
            return this.visibleWithoutValidation;
        }

        for (const key in this.controls) {
            const control = this.controls[key];
            let valueToUse: string;

            if (!control.items.length) {
                continue;
            }

            if (control.main.checked) {
                valueToUse = control.main.value;
            } else {
                const firstItemValue = control.items[0]?.value;

                if (!firstItemValue || control.items.some((i) => i.value !== firstItemValue)) {
                    return false;
                }

                valueToUse = firstItemValue;
            }

            if (valueToUse === this.DEFAULT_VALUE || !valueToUse) {
                return false;
            }

            valuesToCompare.push(valueToUse);
        }

        return valuesToCompare.every((val) => val === valuesToCompare[0]);
    }
}
