import Component from 'ShopUi/models/component';

export default class CartItemNote extends Component {
    protected readyCallback(): void {}

    protected init(): void {
        this.addEventListener('click', (event: Event) => this.onTriggerClick(event));
    }

    protected onTriggerClick(event: Event): void {
        const target = event.target as HTMLElement | null;
        if (!target) {
            return;
        }

        if (target.closest(`.${this.jsName}__edit`)) {
            event.preventDefault();
            const formTarget = this.querySelector<HTMLElement>(`.${this.jsName}__form`);
            const textTarget = this.querySelector<HTMLElement>(`.${this.jsName}__text-wrap`);
            if (formTarget) this.classToggle(formTarget);
            if (textTarget) this.classToggle(textTarget);
            return;
        }

        if (target.closest(`.${this.jsName}__remove`)) {
            event.preventDefault();
            const formTarget = this.querySelector<HTMLElement>(`.${this.jsName}__form`);
            if (!formTarget) {
                return;
            }
            const form = formTarget.getElementsByTagName('form')[0] as HTMLFormElement | undefined;
            if (!form) {
                return;
            }
            const textarea = form.getElementsByTagName('textarea')[0] as HTMLTextAreaElement | undefined;
            if (textarea) {
                textarea.value = '';
            }
            form.querySelector<HTMLButtonElement>('button[type="submit"]')?.click();
        }
    }

    protected classToggle(activeTrigger: HTMLElement): void {
        const isTriggerActive = activeTrigger.classList.contains(this.classToToggle);
        activeTrigger.classList.toggle(this.classToToggle, !isTriggerActive);
    }

    protected get classToToggle(): string {
        return this.getAttribute('class-to-toggle');
    }
}
