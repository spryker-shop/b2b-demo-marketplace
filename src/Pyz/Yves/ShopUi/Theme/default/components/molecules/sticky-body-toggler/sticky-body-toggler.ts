import Component from 'ShopUi/models/component';

export default class StickyBodyToggler extends Component {
    protected triggers: HTMLElement[];

    protected readyCallback(): void {}

    protected init(): void {
        this.triggers = <HTMLElement[]>Array.from(document.getElementsByClassName(this.triggerClassName));
        this.mapEvents();
    }

    protected mapEvents(): void {
        this.triggers.forEach((trigger: HTMLElement) => {
            trigger.addEventListener('click', () => this.toggleStickyBody());
        });
    }

    lock(): void {
        const offset = window.scrollY;

        document.body.style.top = `${-offset}px`;
        document.body.dataset.scrollTo = offset.toString();
        document.body.classList.add(this.classToFixBody);
    }

    unlock(): void {
        if (!this.isLocked) {
            return;
        }

        const scrollTo = parseInt(document.body.dataset.scrollTo || '0') || 0;

        document.body.style.top = '0';
        document.body.classList.remove(this.classToFixBody);
        window.scrollTo(0, scrollTo);
    }

    toggleStickyBody(): void {
        if (this.isLocked) {
            this.unlock();

            return;
        }

        this.lock();
    }

    get isLocked(): boolean {
        return document.body.classList.contains(this.classToFixBody);
    }

    protected get triggerClassName(): string {
        return this.getAttribute('trigger-class-name');
    }

    protected get classToFixBody(): string {
        return this.getAttribute('class-to-fix-body');
    }
}
