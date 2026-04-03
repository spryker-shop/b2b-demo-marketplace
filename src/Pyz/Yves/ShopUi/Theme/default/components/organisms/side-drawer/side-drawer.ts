import SideDrawerCore from 'ShopUi/components/organisms/side-drawer/side-drawer';

export default class SideDrawer extends SideDrawerCore {
    protected overlay: HTMLElement;
    protected isOverlayShown: boolean;
    protected panels: HTMLElement[];
    protected drillDownTriggers: HTMLElement[];
    protected closeButton: HTMLElement;
    protected closeButtonIcon: HTMLElement;
    protected activePanelClass = `${this.name}__panel--active`;
    protected currentPanelId = 'main';
    protected panelHistory: string[] = [];

    protected init(): void {
        this.overlay = <HTMLElement>document.getElementsByClassName(this.overlayClassName)[0];
        this.panels = <HTMLElement[]>Array.from(this.querySelectorAll(`.${this.jsName}__panel`));
        this.drillDownTriggers = <HTMLElement[]>Array.from(this.querySelectorAll(`.${this.jsName}__drill-down-trigger`));
        this.closeButton = <HTMLElement>this.querySelector(`.${this.name}__close`);
        this.closeButtonIcon = <HTMLElement>this.closeButton?.querySelector(`.${this.jsName}__close-icon`);

        super.init();
    }

    protected mapEvents(): void {
        super.mapEvents();

        this.mapWindowResizeEvent();
        this.mapDrillDownEvents();
        this.mapCloseButtonEvent();
        this.mapTriggerDrillDownEvents();
    }

    protected mapTriggerDrillDownEvents(): void {
        this.triggers.forEach((trigger: HTMLElement) => {
            const targetPanelId = trigger.getAttribute('data-target-panel');

            if (targetPanelId) {
                trigger.addEventListener('click', () => {
                    requestAnimationFrame(() => this.navigateTo(targetPanelId));
                });
            }
        });
    }

    protected mapCloseButtonEvent(): void {
        if (!this.closeButton) {
            return;
        }

        this.closeButton.addEventListener('click', (e: Event) => {
            e.preventDefault();
            e.stopImmediatePropagation();

            if (this.currentPanelId !== 'main') {
                this.navigateBack();
            } else {
                this.toggle(false);
            }
        });
    }

    protected mapDrillDownEvents(): void {
        this.drillDownTriggers.forEach((trigger: HTMLElement) => {
            trigger.addEventListener('click', () => {
                const targetPanelId = trigger.getAttribute('data-target-panel');

                if (targetPanelId) {
                    this.navigateTo(targetPanelId);
                }
            });
        });
    }

    protected navigateTo(panelId: string): void {
        this.panels = <HTMLElement[]>Array.from(this.querySelectorAll(`.${this.jsName}__panel`));

        const targetPanel = this.panels.find(
            (panel: HTMLElement) => panel.getAttribute('data-panel-id') === panelId,
        );

        if (!targetPanel) {
            return;
        }

        this.panels.forEach((panel: HTMLElement) => panel.classList.remove(this.activePanelClass));
        targetPanel.classList.add(this.activePanelClass);

        this.panelHistory.push(this.currentPanelId);
        this.currentPanelId = panelId;
        this.syncCloseButton();
    }

    protected navigateBack(): void {
        const previousPanelId = this.panelHistory.pop() ?? 'main';
        this.panels = <HTMLElement[]>Array.from(this.querySelectorAll(`.${this.jsName}__panel`));

        const previousPanel = this.panels.find(
            (panel: HTMLElement) => panel.getAttribute('data-panel-id') === previousPanelId,
        );

        if (!previousPanel) {
            return;
        }

        this.panels.forEach((panel: HTMLElement) => panel.classList.remove(this.activePanelClass));
        previousPanel.classList.add(this.activePanelClass);

        this.currentPanelId = previousPanelId;
        this.syncCloseButton();
    }

    protected syncCloseButton(): void {
        if (!this.closeButtonIcon) {
            return;
        }

        if (this.currentPanelId === 'main') {
            this.closeButtonIcon.textContent = 'close';
            this.closeButton.setAttribute('aria-label', 'Close menu');
        } else {
            this.closeButtonIcon.textContent = 'arrow_back';
            this.closeButton.setAttribute('aria-label', 'Go back');
        }
    }

    protected mapWindowResizeEvent(): void {
        window.addEventListener('resize', () => {
            if (!this.classList.contains(`${this.name}--show`)) {
                return;
            }

            if (window.innerWidth >= this.overlayBreakpoint && this.isOverlayShown) {
                this.toggleOverlay(false);

                return;
            }

            if (window.innerWidth < this.overlayBreakpoint && !this.isOverlayShown) {
                this.toggleOverlay(true);
            }
        });
    }

    protected mapOverlayEvents(): void {
        super.mapOverlayEvents();

        if (this.shouldCloseByOverlayClick) {
            this.mapOverlayClickEvent();
        }
    }

    protected mapOverlayClickEvent(): void {
        this.overlay.addEventListener('click', () => this.toggle(false));
    }

    toggle(isShownForced?: boolean): void {
        const isShown = isShownForced ?? !this.classList.contains(`${this.name}--show`);

        this.classList.toggle(`${this.name}--show`, isShown);
        this.containers.forEach((container: HTMLElement) =>
            container.classList.toggle(this.lockedBodyClassName, isShown),
        );
        this.toggleOverlay(isShown);

        if (!isShown) {
            this.panelHistory = [];
            this.navigateBack();
        }
    }

    protected toggleOverlay(isShown: boolean): void {
        super.toggleOverlay(isShown);

        this.isOverlayShown = isShown;
    }

    protected get lockedBodyClassName(): string {
        return this.getAttribute('locked-body-class-name');
    }

    protected get overlayClassName(): string {
        return this.getAttribute('overlay-class-name');
    }

    protected get shouldCloseByOverlayClick(): boolean {
        return this.hasAttribute('should-close-by-overlay-click');
    }

    protected get overlayBreakpoint(): number {
        return Number(this.getAttribute('overlay-breakpoint'));
    }
}
