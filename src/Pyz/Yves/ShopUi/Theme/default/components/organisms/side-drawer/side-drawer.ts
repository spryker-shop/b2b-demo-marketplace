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

    protected init(): void {
        this.overlay = <HTMLElement>document.getElementsByClassName(this.overlayClassName)[0];
        this.panels = <HTMLElement[]>Array.from(this.querySelectorAll(`.${this.jsName}__panel`));
        this.drillDownTriggers = <HTMLElement[]>Array.from(this.querySelectorAll(`.${this.jsName}__drill-down-trigger`));
        this.closeButton = <HTMLElement>this.querySelector(`.${this.name}__close`);
        this.closeButtonIcon = <HTMLElement>this.closeButton?.querySelector('.material-symbols-outlined');

        super.init();
    }

    protected mapEvents(): void {
        super.mapEvents();

        this.mapWindowResizeEvent();
        this.mapDrillDownEvents();
        this.mapCloseButtonEvent();
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
        const targetPanel = this.panels.find(
            (panel: HTMLElement) => panel.getAttribute('data-panel-id') === panelId,
        );

        if (!targetPanel) {
            return;
        }

        this.panels.forEach((panel: HTMLElement) => panel.classList.remove(this.activePanelClass));
        targetPanel.classList.add(this.activePanelClass);

        this.currentPanelId = panelId;
        this.syncCloseButton();
    }

    protected navigateBack(): void {
        const mainPanel = this.panels.find(
            (panel: HTMLElement) => panel.getAttribute('data-panel-id') === 'main',
        );

        if (!mainPanel) {
            return;
        }

        this.panels.forEach((panel: HTMLElement) => panel.classList.remove(this.activePanelClass));
        mainPanel.classList.add(this.activePanelClass);

        this.currentPanelId = 'main';
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
