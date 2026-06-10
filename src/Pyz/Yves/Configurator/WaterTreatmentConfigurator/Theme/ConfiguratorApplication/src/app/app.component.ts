import { ChangeDetectionStrategy, Component, ViewEncapsulation } from '@angular/core';
import { AsyncPipe } from '@angular/common';
import { map } from 'rxjs';
import { TranslatePipe } from '@ngx-translate/core';
import { ConfiguratorService } from '../services/configurator.service';
import { HeaderComponent } from './header/header.component';
import { FooterComponent } from './footer/footer.component';
import { ProductDetailsComponent } from './product-details/product-details.component';
import { JsonOutputComponent } from './json-output/json-output.component';

@Component({
    selector: 'app-root',
    templateUrl: './app.component.html',
    styleUrl: './app.component.scss',
    changeDetection: ChangeDetectionStrategy.OnPush,
    encapsulation: ViewEncapsulation.None,
    standalone: true,
    imports: [AsyncPipe, TranslatePipe, HeaderComponent, FooterComponent, ProductDetailsComponent, JsonOutputComponent],
})
export class AppComponent {
    constructor(private configuratorService: ConfiguratorService) {}

    loading$ = this.configuratorService.loading$;
    debug$ = this.configuratorService.configuration$.pipe(map((data) => data.debug));
}
