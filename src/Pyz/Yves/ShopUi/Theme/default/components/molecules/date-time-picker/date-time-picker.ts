import Component from 'ShopUi/models/component';
import flatpickr from 'flatpickr';
import { German } from 'flatpickr/dist/l10n/de.js';
import { Options } from 'flatpickr/dist/types/options';

interface ServiceSlot {
    weekday: number;
    from: string;
    to: string;
    everyOtherWeek?: boolean;
}

const EVENT_FETCHED = 'fetched';

export default class DateTimePicker extends Component {
    protected trigger: HTMLInputElement;
    protected dateInput: HTMLInputElement;
    protected dateFromPicker: HTMLInputElement;
    protected dateToPicker: HTMLInputElement;
    protected calendarButton: HTMLButtonElement;
    protected serviceSlotProvider: HTMLElement;
    protected defaultPlaceholder: string = null;

    protected init(): void {
        this.dateInput = this.querySelector<HTMLInputElement>(`.${this.name}__field`);
        this.dateInput.value = this.dateInput.value ? this.formattedDateTime : '';
        this.trigger = this.querySelector<HTMLInputElement>(`.${this.name}__datepicker-input`);
        this.calendarButton = this.querySelector<HTMLButtonElement>(`.${this.name}__calendar-button`);
        this.dateFromPicker = document.querySelector(`[data-id="${this.dateFromId}"]`);
        this.dateToPicker = document.querySelector(`[data-id="${this.dateToId}"]`);

        this.mountEvents();
        this.datePickerInit();
    }

    protected mountEvents(): void {
        this.calendarButton.addEventListener('click', () => this.trigger.focus());
        this.dateInput.addEventListener('blur', () => this.trigger._flatpickr.setDate(this.dateInput.value, true));

        if (this.hasServiceSlots) {
            this.mountServiceSlotEvents();
        }
    }

    /**
     * The service point (e.g. training center) is chosen in an ajax-rendered block, so the allowed
     * slot is only known after that block has been re-rendered.
     */
    protected mountServiceSlotEvents(): void {
        if (!this.servicePointProviderClass) {
            return;
        }

        this.serviceSlotProvider = <HTMLElement>(
            document.getElementsByClassName(this.servicePointProviderClass)[0]
        );
        this.serviceSlotProvider?.addEventListener(EVENT_FETCHED, () => this.datePickerInit());
    }

    protected datePickerInit(): void {
        this.trigger._flatpickr?.destroy();

        const config: Options = {
            locale: this.language === 'de' ? German : 'default',
            enableTime: this.enableTime,
            ...this.config,
            onChange: (selectedDates, dateStr) => {
                this.dateInput.value = dateStr;
                this.dateFromPicker?._flatpickr.set('maxDate', dateStr);
                this.dateToPicker?._flatpickr.set('minDate', dateStr);
            },
        };

        flatpickr(this.trigger, this.hasServiceSlots ? this.serviceSlotConfig(config) : config);

        if (this.hasServiceSlots) {
            this.lockTimeInputs();
        }

        if (this.formattedDateTime && this.trigger.value) {
            this.trigger.value = this.formattedDateTime;
        }
    }

    /**
     * Restricts the picker to the fixed seminar dates of the currently selected service point and
     * writes the slot start time into the submitted value, so no free time input is possible.
     */
    protected serviceSlotConfig(config: Options): Options {
        const slot = this.activeServiceSlot;

        this.toggleDisabledState(!slot);
        this.applySlotPlaceholder(slot);

        if (!slot) {
            return { ...config, enableTime: false, enable: [() => false] };
        }

        const [hour, minute] = slot.from.split(':').map(Number);

        return {
            ...config,
            enableTime: true,
            dateFormat: 'Y-m-d H:i',
            enable: [(date: Date) => this.isSlotDate(date, slot)],
            // The seminar starts at one fixed time, so the time is shown but pinned.
            defaultHour: hour,
            defaultMinute: minute,
            minTime: slot.from,
            maxTime: slot.from,
            onChange: (selectedDates, dateStr) => {
                this.dateInput.value = dateStr;
            },
        };
    }

    /**
     * Flatpickr clamps the time to the slot, but the inputs would still look editable.
     */
    protected lockTimeInputs(): void {
        this.trigger._flatpickr?.calendarContainer
            ?.querySelectorAll<HTMLInputElement>('.flatpickr-time input')
            .forEach((input) => {
                input.readOnly = true;
                input.tabIndex = -1;
            });
    }

    /**
     * States the fixed slot inside the field itself — the placeholder carries the rule until a date is
     * chosen, after which the field shows the booked date and time. Keeps the constraint in the picker
     * instead of in a separate line of text.
     */
    protected applySlotPlaceholder(slot: ServiceSlot | null): void {
        if (this.defaultPlaceholder === null) {
            this.defaultPlaceholder = this.dateInput.placeholder;
        }

        this.dateInput.placeholder = slot
            ? `${this.weekdayName(slot.weekday)}, ${slot.from} – ${slot.to}`
            : this.defaultPlaceholder;
    }

    protected weekdayName(weekday: number): string {
        // 2024-01-07 was a Sunday, so adding the weekday index lands on the wanted day.
        const reference = new Date(Date.UTC(2024, 0, 7 + weekday));

        return reference.toLocaleDateString(this.language === 'de' ? 'de-DE' : 'en-US', {
            weekday: 'long',
            timeZone: 'UTC',
        });
    }

    protected isSlotDate(date: Date, slot: ServiceSlot): boolean {
        if (date.getDay() !== slot.weekday) {
            return false;
        }

        return !slot.everyOtherWeek || this.isoWeek(date) % 2 === 0;
    }

    protected isoWeek(date: Date): number {
        const target = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
        target.setUTCDate(target.getUTCDate() + 4 - (target.getUTCDay() || 7));
        const yearStart = new Date(Date.UTC(target.getUTCFullYear(), 0, 1));

        return Math.ceil(((target.getTime() - yearStart.getTime()) / 86400000 + 1) / 7);
    }

    /**
     * The field stays enabled so it is still submitted (empty) — only manual editing is blocked,
     * as the value must come from a fixed slot of the selected service point.
     */
    protected toggleDisabledState(isDisabled: boolean): void {
        this.dateInput.readOnly = isDisabled;
        this.trigger.disabled = isDisabled;

        if (isDisabled) {
            this.dateInput.value = '';
            this.trigger.value = '';
        }
    }

    protected get activeServiceSlot(): ServiceSlot | null {
        const slots = this.serviceSlots;
        const keyInput = this.servicePointKeySelector
            ? document.querySelector<HTMLInputElement>(this.servicePointKeySelector)
            : null;
        const servicePointKey = keyInput?.value;

        return servicePointKey ? slots[servicePointKey] ?? null : null;
    }

    /**
     * When enabled, the service date may only be one of the fixed slots — a product variant without
     * a matching slot must not fall back to a free date and time.
     */
    protected get hasServiceSlots(): boolean {
        return this.hasAttribute('fixed-service-slots');
    }

    protected get serviceSlots(): Record<string, ServiceSlot> {
        return JSON.parse(this.getAttribute('service-slots') || '{}');
    }

    protected get servicePointKeySelector(): string {
        return this.getAttribute('service-point-key-selector');
    }

    protected get servicePointProviderClass(): string {
        return this.getAttribute('service-point-provider-class');
    }

    protected get formattedDateTime(): string {
        return this.getAttribute('formatted-date-time');
    }

    protected get dateToId(): string {
        return this.getAttribute('date-to-id');
    }

    protected get dateFromId(): string {
        return this.getAttribute('date-from-id');
    }

    protected get config(): object {
        return JSON.parse(this.getAttribute('config'));
    }

    protected get language(): string {
        return this.getAttribute('language');
    }

    protected get enableTime(): boolean {
        return this.hasAttribute('enable-time');
    }
}
