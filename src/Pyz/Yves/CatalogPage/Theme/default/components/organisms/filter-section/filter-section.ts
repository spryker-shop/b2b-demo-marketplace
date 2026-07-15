import Component from 'ShopUi/models/component';
import StickyBodyToggler from 'src/ShopUi/components/molecules/sticky-body-toggler/sticky-body-toggler';
import FilterSearch from '../../molecules/filter-search/filter-search';

interface InputSnapshotEntry {
    element: HTMLInputElement | HTMLSelectElement;
    checked: boolean;
    value: string;
}

export default class FilterSection extends Component {
    protected triggers: HTMLElement[];
    protected closeButton: HTMLButtonElement;
    protected applyButton: HTMLButtonElement;
    protected clearButton: HTMLButtonElement;
    protected filterSearch: FilterSearch;
    protected snapshot: InputSnapshotEntry[] = [];
    protected mobileMedia: MediaQueryList;
    protected stickyBodyToggler: StickyBodyToggler;
    protected isOpen = false;

    protected readyCallback(): void {}

    protected init(): void {
        this.triggers = <HTMLElement[]>Array.from(document.getElementsByClassName(this.triggerClassName));
        this.closeButton = <HTMLButtonElement>this.querySelector(`.${this.jsName}__close`);
        this.applyButton = <HTMLButtonElement>this.querySelector(`.${this.jsName}__apply`);
        this.clearButton = <HTMLButtonElement>this.querySelector(`.${this.jsName}__clear`);
        this.filterSearch = <FilterSearch>this.querySelector('filter-search');
        this.stickyBodyToggler = document.querySelector<StickyBodyToggler>('.js-sticky-body-toggler-root');
        this.mobileMedia = window.matchMedia(`(max-width: ${this.overlayBreakpoint - 1}px)`);
        this.mapEvents();
    }

    protected mapEvents(): void {
        this.triggers.forEach((trigger: HTMLElement) => {
            trigger.addEventListener('click', () => this.open());
        });
        this.closeButton?.addEventListener('click', () => this.close());
        this.applyButton?.addEventListener('click', () => this.onApply());
        this.clearButton?.addEventListener('click', () => this.clearPendingFilters());
        this.addEventListener('change', (event: Event) => this.onSectionChange(event), true);
        this.mobileMedia.addEventListener('change', (event: MediaQueryListEvent) => this.onBreakpointChange(event));
        window.addEventListener('pageshow', (event: PageTransitionEvent) => this.onPageShow(event));
    }

    protected open(): void {
        if (this.isOpen) {
            return;
        }

        this.isOpen = true;
        this.takeSnapshot();
        this.stickyBodyToggler?.lock();
        this.classList.remove(this.classToToggle);
        this.setAttribute('role', 'dialog');
        this.setAttribute('aria-modal', 'true');
        this.setAttribute('aria-label', this.dialogLabel);
        this.updateTriggersExpanded(true);
    }

    protected close(): void {
        if (!this.isOpen) {
            return;
        }

        this.isOpen = false;
        this.restoreSnapshot();
        this.teardownOverlay();
    }

    protected onApply(): void {
        if (!this.applyButton) {
            return;
        }

        this.applyButton.disabled = true;
    }

    protected teardownOverlay(): void {
        this.filterSearch?.reset();
        this.stickyBodyToggler?.unlock();
        this.classList.add(this.classToToggle);
        this.removeAttribute('role');
        this.removeAttribute('aria-modal');
        this.removeAttribute('aria-label');
        this.updateTriggersExpanded(false);
    }

    protected onBreakpointChange(event: MediaQueryListEvent): void {
        if (!event.matches && this.isOpen) {
            this.isOpen = false;
            this.teardownOverlay();
        }
    }

    protected onPageShow(event: PageTransitionEvent): void {
        if (!event.persisted) {
            return;
        }

        if (this.applyButton) {
            this.applyButton.disabled = false;
        }

        if (this.isOpen) {
            this.isOpen = false;
            this.teardownOverlay();
        }
    }

    protected onSectionChange(event: Event): void {
        if (this.isOpen && this.isMobile) {
            event.stopPropagation();
        }
    }

    protected clearPendingFilters(): void {
        this.snapshotTargets.forEach((element: HTMLInputElement | HTMLSelectElement) => {
            if (element instanceof HTMLInputElement && ['checkbox', 'radio'].includes(element.type)) {
                element.checked = false;

                return;
            }

            element.value = '';
        });
    }

    protected takeSnapshot(): void {
        this.snapshot = this.snapshotTargets.map((element: HTMLInputElement | HTMLSelectElement) => ({
            element,
            checked: (<HTMLInputElement>element).checked,
            value: element.value,
        }));
    }

    protected restoreSnapshot(): void {
        this.snapshot.forEach((entry: InputSnapshotEntry) => {
            if (entry.element instanceof HTMLInputElement && ['checkbox', 'radio'].includes(entry.element.type)) {
                entry.element.checked = entry.checked;

                return;
            }

            entry.element.value = entry.value;
        });
        this.snapshot = [];
    }

    protected get snapshotTargets(): (HTMLInputElement | HTMLSelectElement)[] {
        return <(HTMLInputElement | HTMLSelectElement)[]>(
            Array.from(this.querySelectorAll('input, select')).filter(
                (element: HTMLElement) => !element.closest('filter-search'),
            )
        );
    }

    protected updateTriggersExpanded(isExpanded: boolean): void {
        this.triggers.forEach((trigger: HTMLElement) => trigger.setAttribute('aria-expanded', String(isExpanded)));
    }

    protected get isMobile(): boolean {
        return this.mobileMedia.matches;
    }

    protected get triggerClassName(): string {
        return this.getAttribute('trigger-class-name');
    }

    protected get classToToggle(): string {
        return this.getAttribute('class-to-toggle');
    }

    protected get overlayBreakpoint(): number {
        return parseInt(this.getAttribute('overlay-breakpoint')) || 768;
    }

    protected get dialogLabel(): string {
        return this.getAttribute('dialog-label') || '';
    }
}
