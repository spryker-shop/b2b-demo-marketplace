import ReactiveComponent from 'src/ShopUi/models/reactive/reactive-component';

export default class ReactiveDemo extends ReactiveComponent {
    protected readyCallback(): void {}

    protected init(): void {
        this.mapEvents();
        super.init();
    }

    protected mapEvents(): void {
        this.incrementTrigger?.addEventListener('click', () => this.increment());
        this.toggleTrigger?.addEventListener('click', () => this.toggleCmsBlock());
        this.categoryInput?.addEventListener('input', () => this.updateCategory());
    }

    protected increment(): void {
        this.store.set('demo.counter', (this.store.get<number>('demo.counter') ?? 0) + 1);
    }

    protected toggleCmsBlock(): void {
        this.store.set('demo.cmsBlockHidden', !this.store.get<boolean>('demo.cmsBlockHidden'));
    }

    protected updateCategory(): void {
        this.store.set('catalog.cmsBlock.idCategory', this.categoryInput.value);
    }

    protected get incrementTrigger(): HTMLButtonElement | undefined {
        return this.getElementsByClassName(`${this.jsName}__increment`)[0] as HTMLButtonElement;
    }

    protected get toggleTrigger(): HTMLButtonElement | undefined {
        return this.getElementsByClassName(`${this.jsName}__toggle`)[0] as HTMLButtonElement;
    }

    protected get categoryInput(): HTMLInputElement | undefined {
        return this.getElementsByClassName(`${this.jsName}__category-input`)[0] as HTMLInputElement;
    }
}
