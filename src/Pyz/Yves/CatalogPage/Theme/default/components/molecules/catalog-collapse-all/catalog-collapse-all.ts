import Component from 'ShopUi/models/component';

export default class CatalogCollapseAll extends Component {
    protected trigger: HTMLButtonElement;
    protected section: HTMLElement;

    protected readyCallback(): void {}

    protected init(): void {
        this.trigger = <HTMLButtonElement>this.querySelector(`.${this.jsName}__trigger`);
        this.section = <HTMLElement>this.closest(this.sectionSelector);
        this.mapEvents();
    }

    protected mapEvents(): void {
        this.trigger.addEventListener('click', () => this.onToggleAll());
        this.section.addEventListener('click', (event: Event) => this.onUnitTriggerClick(event));
    }

    protected onUnitTriggerClick(event: Event): void {
        const trigger = (<HTMLElement>event.target).closest(`.${this.triggerClass}`);

        if (!trigger) {
            return;
        }

        const unit = trigger.closest(`.${this.unitClass}`);

        if (unit) {
            unit.classList.toggle(this.collapsedClass);
        }
    }

    protected onToggleAll(): void {
        const shouldCollapse = !this.classList.contains(this.collapsedClass);

        this.units.forEach((unit: HTMLElement) => unit.classList.toggle(this.collapsedClass, shouldCollapse));
        this.classList.toggle(this.collapsedClass, shouldCollapse);
    }

    protected get units(): HTMLElement[] {
        return <HTMLElement[]>Array.from(this.section.getElementsByClassName(this.unitClass));
    }

    protected get sectionSelector(): string {
        return this.getAttribute('section-selector');
    }

    protected get unitClass(): string {
        return this.getAttribute('unit-class');
    }

    protected get triggerClass(): string {
        return this.getAttribute('trigger-class');
    }

    protected get collapsedClass(): string {
        return this.getAttribute('collapsed-class');
    }
}
