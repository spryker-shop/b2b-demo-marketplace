import Component from 'ShopUi/models/component';

const HIDDEN_CLASS = 'is-hidden';
const SEARCHING_CLASS = 'is-searching';

export default class FilterSearch extends Component {
    protected input: HTMLInputElement;
    protected section: HTMLElement;
    protected groups: HTMLElement[];
    protected emptyMessage: HTMLElement;
    protected collapseAll: HTMLElement;

    protected readyCallback(): void {}

    protected init(): void {
        this.input = <HTMLInputElement>this.querySelector('input');
        this.section = <HTMLElement>this.closest(this.getAttribute('section-selector'));
        this.groups = <HTMLElement[]>Array.from(document.querySelectorAll(this.getAttribute('group-selector')));
        this.emptyMessage = <HTMLElement>this.querySelector(`.${this.jsName}__empty`);
        this.collapseAll = <HTMLElement>this.section?.querySelector(this.getAttribute('collapse-all-selector'));
        this.mapEvents();
    }

    protected mapEvents(): void {
        this.input.addEventListener('input', () => this.onInput());
    }

    reset(): void {
        if (!this.input.value) {
            return;
        }

        this.input.value = '';
        this.onInput();
    }

    protected onInput(): void {
        const query = this.input.value.trim().toLowerCase();
        const isSearching = query.length > 0;
        const rowSelector = this.getAttribute('row-selector');

        if (this.section) {
            this.section.classList.toggle(SEARCHING_CLASS, isSearching);
        }

        const titleSelector = this.getAttribute('title-selector');
        let hasAnyMatch = false;

        this.groups.forEach((group: HTMLElement) => {
            const title = <HTMLElement>group.querySelector(titleSelector);
            const titleText = (title?.textContent || '').trim().toLowerCase();
            const titleMatch = isSearching && titleText.indexOf(query) !== -1;

            const rows = <HTMLElement[]>Array.from(group.querySelectorAll(rowSelector));
            let hasMatch = titleMatch;

            rows.forEach((row: HTMLElement) => {
                const text = (row.textContent || '').trim().toLowerCase();
                const isMatch = !isSearching || titleMatch || text.indexOf(query) !== -1;

                row.classList.toggle(HIDDEN_CLASS, !isMatch);

                if (isMatch) {
                    hasMatch = true;
                }
            });

            group.classList.toggle(HIDDEN_CLASS, isSearching && !hasMatch);

            if (hasMatch) {
                hasAnyMatch = true;
            }
        });

        this.emptyMessage?.classList.toggle(HIDDEN_CLASS, !isSearching || hasAnyMatch);

        const hasVisibleGroups = this.groups.some((group: HTMLElement) => !group.classList.contains(HIDDEN_CLASS));
        this.collapseAll?.classList.toggle(HIDDEN_CLASS, isSearching && !hasVisibleGroups);
    }
}
