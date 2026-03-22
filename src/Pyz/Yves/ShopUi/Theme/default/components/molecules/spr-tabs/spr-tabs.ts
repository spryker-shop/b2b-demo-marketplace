import Component from 'ShopUi/models/component';

export default class SprTabs extends Component {
    protected triggers!: HTMLButtonElement[];
    protected panels!: HTMLElement[];

    protected readyCallback(): void {}

    protected init(): void {
        this.triggers = Array.from(this.querySelectorAll<HTMLButtonElement>(`.${this.jsName}__trigger`));
        this.panels = Array.from(this.querySelectorAll<HTMLElement>(`.${this.jsName}__panel`));
        this.mapEvents();
    }

    protected mapEvents(): void {
        this.triggers.forEach((trigger: HTMLButtonElement) => {
            trigger.addEventListener('click', () => this.onTriggerClick(trigger));
        });
    }

    protected onTriggerClick(activeTrigger: HTMLButtonElement): void {
        this.activateTab(activeTrigger.dataset.tabId!);
    }

    protected activateTab(id: string): void {
        this.triggers.forEach((trigger: HTMLButtonElement) => {
            const isActive = trigger.dataset.tabId === id;
            trigger.classList.toggle(this.activeClass, isActive);
            trigger.setAttribute('aria-selected', String(isActive));
        });

        this.panels.forEach((panel: HTMLElement) => {
            panel.classList.toggle(this.panelHiddenClass, panel.dataset.tabId !== id);
        });
    }

    protected get activeClass(): string {
        return this.getAttribute('active-class')!;
    }

    protected get panelHiddenClass(): string {
        return this.getAttribute('panel-hidden-class')!;
    }
}
