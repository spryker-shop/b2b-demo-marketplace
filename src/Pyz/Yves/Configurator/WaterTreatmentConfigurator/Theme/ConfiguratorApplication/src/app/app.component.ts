import { ChangeDetectionStrategy, Component, ViewEncapsulation } from '@angular/core';

import { map } from 'rxjs';
import { ConfiguratorService } from '../services/configurator.service';

@Component({
    selector: 'app-root',
    templateUrl: './app.component.html',
    styleUrls: ['./app.component.scss'],
    changeDetection: ChangeDetectionStrategy.OnPush,
    encapsulation: ViewEncapsulation.None,
})
export class AppComponent {
    constructor(private configuratorService: ConfiguratorService) {}

    loading$ = this.configuratorService.loading$;
    debug$ = this.configuratorService.configuration$.pipe(map((data) => data.debug));
}
