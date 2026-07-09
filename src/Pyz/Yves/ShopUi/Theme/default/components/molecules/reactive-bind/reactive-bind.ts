import ReactiveComponent from 'src/ShopUi/models/reactive/reactive-component';

export default class ReactiveBind extends ReactiveComponent {
    protected readyCallback(): void {}

    protected init(): void {
        this.hydrateFromPayload();
        super.init();
    }

    protected hydrateFromPayload(): void {
        const payload = this.querySelector<HTMLScriptElement>(':scope > script[type="application/json"]');

        if (!payload?.textContent) {
            return;
        }

        this.store.hydrate(JSON.parse(payload.textContent), this.getAttribute('prefix') ?? '');
    }
}
