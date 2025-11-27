import Component from 'ShopUi/models/component';

export default class FlashMessage extends Component {
    readonly defaultDuration: number = 5000;
    protected static queue: FlashMessage[] = [];
    protected static activeMessages: Set<FlashMessage> = new Set();
    durationTimeoutId: number;
    protected isVisible = false;

    protected readyCallback(): void {}

    protected init(): void {
        this.mapEvents();
        this.updateVisibleLimitFromDataset();
        FlashMessage.enqueue(this);
    }

    protected mapEvents(): void {
        this.addEventListener('click', (event: Event) => this.onClick(event));
    }

    protected onClick(event: Event): void {
        event.preventDefault();
        this.hide();
    }

    showFor(duration: number) {
        if (this.isVisible) {
            return;
        }

        this.isVisible = true;
        this.classList.add(`${this.name}--show`);
        this.durationTimeoutId = window.setTimeout(() => this.hide(), duration);
    }

    hide() {
        clearTimeout(this.durationTimeoutId);

        if (!this.isVisible) {
            return;
        }

        this.classList.remove(`${this.name}--show`);
        this.isVisible = false;
        FlashMessage.onHidden(this);
    }

    protected updateVisibleLimitFromDataset(): void {
        const maxVisibleAttr = this.getAttribute('max-visible-messages');

        if (!maxVisibleAttr) {
            return;
        }

        const parsedValue = Number(maxVisibleAttr);

        if (!Number.isNaN(parsedValue) && parsedValue > 0) {
            FlashMessage.maxVisibleMessages = parsedValue;
        }
    }

    /**
     * Adds the instance to the queue and tries to show messages while we have free slots.
     */
    protected static enqueue(instance: FlashMessage): void {
        this.queue.push(instance);
        this.processQueue();
    }

    /**
     * Shows queued messages if there is available space.
     */
    protected static processQueue(): void {
        while (this.activeMessages.size < this.maxVisibleMessages && this.queue.length > 0) {
            const nextMessage = this.queue.shift();

            if (!nextMessage) {
                return;
            }

            this.activeMessages.add(nextMessage);
            nextMessage.showFor(nextMessage.getDurationFromDataset());
        }
    }

    /**
     * Called when a message is hidden to free a slot for the next one in the queue.
     */
    protected static onHidden(instance: FlashMessage): void {
        if (this.activeMessages.delete(instance)) {
            this.processQueue();
        }
    }

    /**
     * Uses `data-duration` per message (if provided) otherwise falls back to the default duration.
     */
    protected getDurationFromDataset(): number {
        const durationAttr = this.getAttribute('default-duration');

        if (!durationAttr) {
            return this.defaultDuration;
        }

        const parsedValue = Number(durationAttr);

        if (Number.isNaN(parsedValue) || parsedValue <= 0) {
            return this.defaultDuration;
        }

        return parsedValue;
    }
}
