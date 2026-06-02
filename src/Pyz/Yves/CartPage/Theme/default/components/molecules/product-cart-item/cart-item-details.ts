import Component from 'ShopUi/models/component';

const ROOT = 'product-cart-item';
const EXPANDED = `${ROOT}__details--expanded`;
const MENU_OPEN = `${ROOT}__comment-menu--open`;
const CHIP_FILLED = `${ROOT}__context-item--filled`;

export default class CartItemDetails extends Component {
    protected toggle: HTMLButtonElement | null;
    protected storageKey = '';

    protected readyCallback(): void {}

    protected init(): void {
        this.toggle = this.querySelector<HTMLButtonElement>(`.${ROOT}__toggle`);
        const id = this.getAttribute('data-cart-item-id') || '';
        this.storageKey = id ? `cart-item-details:expanded:${id}` : '';
        this.restoreExpandedState();
        this.mapToggle();
        this.enhanceComments();
        this.updateContextStrip();
        this.closeMenuOnOutsideClick();
    }

    protected get isExpanded(): boolean {
        return this.classList.contains(EXPANDED);
    }

    protected restoreExpandedState(): void {
        if (!this.storageKey) {
            return;
        }

        try {
            if (sessionStorage.getItem(this.storageKey) === '1') {
                this.classList.add(EXPANDED);
                this.toggle?.setAttribute('aria-expanded', 'true');
            }
        } catch (e) {
            //
        }
    }

    protected persistExpandedState(): void {
        if (!this.storageKey) {
            return;
        }

        try {
            sessionStorage.setItem(this.storageKey, this.isExpanded ? '1' : '0');
        } catch (e) {
            //
        }
    }

    protected mapToggle(): void {
        if (!this.toggle) {
            return;
        }

        this.toggle.addEventListener('click', (event: Event) => {
            event.preventDefault();
            this.classList.toggle(EXPANDED);
            this.toggle?.setAttribute('aria-expanded', String(this.isExpanded));
            this.persistExpandedState();
        });
    }

    protected enhanceComments(): void {
        const container = this.querySelector<HTMLElement>(`.${ROOT}__comments`);
        const textWrap = container?.querySelector<HTMLElement>('.cart-item-note__text-wrap');
        const text = container?.querySelector<HTMLElement>('.cart-item-note__text');
        const editBtn = container?.querySelector<HTMLElement>('.js-cart-item-note__edit');
        const removeBtn = container?.querySelector<HTMLElement>('.js-cart-item-note__remove');
        const nativeActions = container?.querySelector<HTMLElement>('.cart-item-note-actions');

        if (!textWrap || !text || !editBtn || !removeBtn || !text.textContent?.trim()) {
            return;
        }

        textWrap.classList.add(`${ROOT}__comment`);
        textWrap.setAttribute('data-qa', 'cart-item-comment-filled');
        nativeActions?.classList.add('is-hidden');

        const menu = this.buildKebabMenu();
        textWrap.appendChild(menu.wrapper);

        menu.edit.addEventListener('click', () => {
            editBtn.click();
            menu.wrapper.classList.remove(MENU_OPEN);
        });

        menu.remove.addEventListener('click', () => {
            removeBtn.click();
            menu.wrapper.classList.remove(MENU_OPEN);
        });
    }

    protected buildKebabMenu(): {
        wrapper: HTMLElement;
        edit: HTMLButtonElement;
        remove: HTMLButtonElement;
    } {
        const wrapper = document.createElement('div');
        wrapper.className = `${ROOT}__comment-menu`;

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = `${ROOT}__comment-menu-trigger`;
        trigger.setAttribute('aria-label', 'Comment actions');
        trigger.setAttribute('data-qa', 'cart-item-comment-menu');
        trigger.innerHTML = '<span></span>';

        const list = document.createElement('div');
        list.className = `${ROOT}__comment-menu-list`;

        const edit = document.createElement('button');
        edit.type = 'button';
        edit.textContent = this.getAttribute('edit-label') || 'Edit';
        edit.setAttribute('data-qa', 'cart-item-comment-edit');

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.textContent = this.getAttribute('delete-label') || 'Delete';
        remove.setAttribute('data-qa', 'cart-item-comment-delete');

        list.appendChild(edit);
        list.appendChild(remove);
        wrapper.appendChild(trigger);
        wrapper.appendChild(list);

        trigger.addEventListener('click', (event: Event) => {
            event.stopPropagation();
            wrapper.classList.toggle(MENU_OPEN);
        });

        return { wrapper, edit, remove };
    }

    protected updateContextStrip(): void {
        const assetChip = this.querySelector<HTMLButtonElement>(`.${this.jsName}__context--asset`);
        const commentChip = this.querySelector<HTMLButtonElement>(`.${this.jsName}__context--comment`);

        if (assetChip) {
            this.bindContextChip(assetChip, this.detectAssetName(), () => this.openAssetPicker());
        }

        if (commentChip) {
            const count = this.detectCommentCount();
            const filled = count > 0 ? this.formatCommentLabel(commentChip, count) : '';
            this.bindContextChip(commentChip, filled, () => this.openComment());
        }
    }

    protected bindContextChip(chip: HTMLButtonElement, filledLabel: string, emptyAction: () => void): void {
        const textEl = chip.querySelector<HTMLElement>(`.${ROOT}__context-text`);
        if (!textEl) {
            return;
        }

        if (filledLabel) {
            textEl.textContent = filledLabel;
            chip.classList.add(CHIP_FILLED);
            chip.removeAttribute('type');
            chip.setAttribute('aria-disabled', 'true');
        } else {
            textEl.textContent = chip.getAttribute('data-empty-label') || textEl.textContent || '';
            chip.classList.remove(CHIP_FILLED);
            chip.addEventListener('click', (event: Event) => {
                event.preventDefault();
                emptyAction();
            });
        }
    }

    protected detectAssetName(): string {
        const selector = this.querySelector<HTMLElement>(`.${ROOT}__asset .asset-selector.is-selected`);
        if (!selector) {
            return '';
        }
        return (
            this.querySelector<HTMLElement>(
                `.${ROOT}__asset .js-asset-selector__asset-name`,
            )?.textContent?.trim() || ''
        );
    }

    protected detectCommentCount(): number {
        const text = this.querySelector<HTMLElement>(`.${ROOT}__comments .cart-item-note__text`);
        return text && text.textContent && text.textContent.trim().length > 0 ? 1 : 0;
    }

    protected formatCommentLabel(chip: HTMLButtonElement, count: number): string {
        const singular = chip.getAttribute('data-comment-singular') || 'Comment';
        const plural = chip.getAttribute('data-comment-plural') || singular;
        return `${count} ${count === 1 ? singular : plural}`;
    }

    protected openAssetPicker(): void {
        this.ensureExpanded();
        const trigger = this.querySelector<HTMLElement>(
            `.${ROOT}__asset [class*="js-asset-selector__trigger"]`,
        );
        trigger?.click();
    }

    protected openComment(): void {
        this.ensureExpanded();
        const trigger = this.querySelector<HTMLElement>(`.${ROOT}__comments .js-cart-item-note__trigger`);
        trigger?.click();
        const field = this.querySelector<HTMLTextAreaElement | HTMLInputElement>(
            `.${ROOT}__comments textarea, .${ROOT}__comments input[type="text"]`,
        );
        setTimeout(() => field?.focus(), 50);
    }

    protected ensureExpanded(): void {
        if (this.isExpanded) {
            return;
        }
        this.classList.add(EXPANDED);
        this.toggle?.setAttribute('aria-expanded', 'true');
        this.persistExpandedState();
    }

    protected closeMenuOnOutsideClick(): void {
        document.addEventListener('click', () => {
            this.querySelectorAll(`.${MENU_OPEN}`).forEach((menu) => menu.classList.remove(MENU_OPEN));
        });
    }
}
