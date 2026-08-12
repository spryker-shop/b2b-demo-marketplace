import ReviewAddProductPickerCore from 'OrderExperienceManagement/components/molecules/review-add-product-picker/review-add-product-picker';

export default class ReviewAddProductPicker extends ReviewAddProductPickerCore {
    protected static readonly AUTOCOMPLETE_TEXT_INPUT_CLASS = 'js-product-search-autocomplete-form__input';

    protected reset(): void {
        super.reset();

        const searchTextInput = this.querySelector<HTMLInputElement>(
            `.${ReviewAddProductPicker.AUTOCOMPLETE_TEXT_INPUT_CLASS}`,
        );

        if (searchTextInput) {
            searchTextInput.value = '';
        }
    }
}
