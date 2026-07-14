import { Store, Unsubscribe, createStore } from './store';

const TEXT_DIRECTIVE = 'data-bind-text';
const ATTRIBUTE_DIRECTIVE = 'data-bind-attr';
const CLASS_DIRECTIVE = 'data-bind-class';
const DIRECTIVE_SELECTOR = `[${TEXT_DIRECTIVE}], [${ATTRIBUTE_DIRECTIVE}], [${CLASS_DIRECTIVE}]`;

type BindingPair = [string, string];

function parsePairs(rawValue: string): BindingPair[] {
    return rawValue
        .split(',')
        .map((pair) => pair.split(':').map((part) => part.trim()))
        .filter((parts) => parts.length === 2 && parts[0] && parts[1]) as BindingPair[];
}

function bindText(element: HTMLElement, path: string, store: Store): Unsubscribe {
    return store.subscribe(path, (value) => {
        element.textContent = value === undefined || value === null ? '' : String(value);
    });
}

function applyAttribute(element: HTMLElement, name: string, value: unknown): void {
    if (value === undefined || value === null || value === false) {
        element.removeAttribute(name);

        return;
    }

    element.setAttribute(name, value === true ? '' : String(value));
}

function bindAttributes(element: HTMLElement, rawValue: string, store: Store): Unsubscribe[] {
    return parsePairs(rawValue).map(([name, path]) =>
        store.subscribe(path, (value) => applyAttribute(element, name, value)),
    );
}

function bindClasses(element: HTMLElement, rawValue: string, store: Store): Unsubscribe[] {
    return parsePairs(rawValue).map(([className, path]) =>
        store.subscribe(path, (value) => element.classList.toggle(className, Boolean(value))),
    );
}

const activeBindings = new WeakSet<HTMLElement>();

export function bindElement(element: HTMLElement, store: Store = createStore()): Unsubscribe[] {
    if (activeBindings.has(element)) {
        return [];
    }

    const disposers: Unsubscribe[] = [];
    const textPath = element.getAttribute(TEXT_DIRECTIVE);
    const attributePairs = element.getAttribute(ATTRIBUTE_DIRECTIVE);
    const classPairs = element.getAttribute(CLASS_DIRECTIVE);

    if (textPath) {
        disposers.push(bindText(element, textPath, store));
    }

    if (attributePairs) {
        disposers.push(...bindAttributes(element, attributePairs, store));
    }

    if (classPairs) {
        disposers.push(...bindClasses(element, classPairs, store));
    }

    if (!disposers.length) {
        return disposers;
    }

    activeBindings.add(element);

    return [
        () => {
            disposers.forEach((dispose) => dispose());
            activeBindings.delete(element);
        },
    ];
}

export function bindTree(root: HTMLElement, store: Store = createStore()): Unsubscribe[] {
    const elements = Array.from(root.querySelectorAll<HTMLElement>(DIRECTIVE_SELECTOR));

    if (root.matches(DIRECTIVE_SELECTOR)) {
        elements.unshift(root);
    }

    return elements.flatMap((element) => bindElement(element, store));
}
