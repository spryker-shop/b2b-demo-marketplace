import Component from 'ShopUi/models/component';

const HIDDEN_CLASS = 'is-hidden';
const SEARCHING_CLASS = 'is-searching';

export default class FilterSearch extends Component {
    protected input: HTMLInputElement;
    protected section: HTMLElement;
    protected groups: HTMLElement[];

    protected readyCallback(): void {}

    protected init(): void {
        this.input = <HTMLInputElement>this.querySelector('input');
        this.section = <HTMLElement>this.closest(this.getAttribute('section-selector'));
        this.groups = <HTMLElement[]>Array.from(document.querySelectorAll(this.getAttribute('group-selector')));
        this.mapEvents();
    }

    protected mapEvents(): void {
        this.input.addEventListener('input', () => this.onInput());
    }

    protected onInput(): void {
        const query = this.input.value.trim().toLowerCase();
        const isSearching = query.length > 0;
        const rowSelector = this.getAttribute('row-selector');

        if (this.section) {
            this.section.classList.toggle(SEARCHING_CLASS, isSearching);
        }

        this.groups.forEach((group: HTMLElement) => {
            const rows = <HTMLElement[]>Array.from(group.querySelectorAll(rowSelector));
            let hasMatch = false;

            rows.forEach((row: HTMLElement) => {
                const text = (row.textContent || '').trim().toLowerCase();
                const isMatch = !isSearching || text.indexOf(query) !== -1;

                row.classList.toggle(HIDDEN_CLASS, !isMatch);

                if (isMatch) {
                    hasMatch = true;
                }
            });

            if (rows.length > 0) {
                group.classList.toggle(HIDDEN_CLASS, isSearching && !hasMatch);
            }
        });
    }
}
