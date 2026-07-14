import Component from 'ShopUi/models/component';
import { Store, Unsubscribe, createStore } from './store';
import { bindTree } from './directives';

export default abstract class ReactiveComponent extends Component {
    protected store: Store = createStore();
    private disposers: Unsubscribe[] = [];

    protected init(): void {
        this.addDisposers(...bindTree(this, this.store));
        super.init();
    }

    protected subscribe<T>(path: string, callback: (value: T) => void): void {
        this.addDisposers(this.store.subscribe(path, callback));
    }

    protected addDisposers(...disposers: Unsubscribe[]): void {
        this.disposers.push(...disposers);
    }

    disconnectedCallback(): void {
        this.disposers.forEach((dispose) => dispose());
        this.disposers = [];
    }
}
