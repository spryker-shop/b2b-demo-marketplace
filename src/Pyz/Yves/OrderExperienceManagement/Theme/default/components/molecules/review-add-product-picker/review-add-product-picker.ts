import ReviewAddProductPickerCore from 'OrderExperienceManagement/components/molecules/review-add-product-picker/review-add-product-picker';

export default class ReviewAddProductPicker extends ReviewAddProductPickerCore {
    protected static readonly AUTOCOMPLETE_TEXT_INPUT_CLASS = 'js-product-search-autocomplete-form__input';

    protected onSearchInput(event: Event): void {
        const input = event.target as HTMLInputElement | null;

        if (!input?.classList.contains(ReviewAddProductPicker.AUTOCOMPLETE_TEXT_INPUT_CLASS)) {
            return;
        }

        if (input.value.trim() === '' && this.selectedSku !== '') {
            this.clearSelection();
        }
    }

    protected reset(): void {
        super.reset();

        const searchTextInput = this.querySelector<HTMLInputElement>(
            `.${ReviewAddProductPicker.AUTOCOMPLETE_TEXT_INPUT_CLASS}`,
        );

        console.log(this.querySelector<HTMLInputElement>(`.${ReviewAddProductPicker.AUTOCOMPLETE_TEXT_INPUT_CLASS}`));

        if (searchTextInput) {
            searchTextInput.value = '';
        }
    }
}
