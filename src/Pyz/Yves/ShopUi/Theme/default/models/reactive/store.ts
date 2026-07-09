import { Signal, signal, effect } from '@preact/signals-core';

export type Unsubscribe = () => void;

export class Store {
    protected signals = new Map<string, Signal<unknown>>();

    ensureSignal<T>(path: string, initialValue?: T): Signal<T> {
        const existing = this.resolveSignal<T>(path);

        if (existing) {
            return existing;
        }

        const created = signal<T>(initialValue as T);
        this.signals.set(path, created);

        return created;
    }

    resolveSignal<T>(path: string): Signal<T> | undefined {
        return this.signals.get(path) as Signal<T> | undefined;
    }

    get<T>(path: string): T | undefined {
        return this.resolveSignal<T>(path)?.value;
    }

    set<T>(path: string, value: T): void {
        this.ensureSignal<T>(path).value = value;
    }

    subscribe<T>(path: string, callback: (value: T) => void): Unsubscribe {
        const target = this.ensureSignal<T>(path);

        return effect(() => callback(target.value));
    }

    hydrate(data: Record<string, unknown>, prefix = ''): void {
        Object.entries(data).forEach(([key, value]) => {
            const path = prefix ? `${prefix}.${key}` : key;

            if (this.isPlainObject(value)) {
                this.hydrate(value as Record<string, unknown>, path);

                return;
            }

            this.set(path, value);
        });
    }

    protected isPlainObject(value: unknown): boolean {
        return typeof value === 'object' && value !== null && !Array.isArray(value);
    }
}

interface StoreScope extends Window {
    __shopUiReactiveStore?: Store;
}

export function createStore(): Store {
    const scope = window as StoreScope;

    if (!scope.__shopUiReactiveStore) {
        scope.__shopUiReactiveStore = new Store();
    }

    return scope.__shopUiReactiveStore;
}
