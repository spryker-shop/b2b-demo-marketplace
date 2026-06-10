import { ChangeDetectionStrategy, Component, EventEmitter, Input, Output } from '@angular/core';

@Component({
    selector: 'app-configurator-item',
    templateUrl: './configurator-item.component.html',
    styleUrls: ['./configurator-item.component.scss'],
    changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ConfiguratorItemComponent {
    @Input() value: string;
    @Input() price: number;
    @Input() tooltip: string;
    @Input() currency: string;
    @Input() checked = false;
    @Input() disabled?: string | boolean;
    @Output() onChange = new EventEmitter<string>();

    withTooltip(): boolean {
        return typeof this.disabled === 'string';
    }

    onClick() {
        if (this.disabled) return;
        this.checked = !this.checked;
        this.onChange.emit(this.checked ? null : this.value);
    }
}
