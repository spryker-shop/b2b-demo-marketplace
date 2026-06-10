import { ChangeDetectionStrategy, Component, ViewChild } from '@angular/core';
import { AsyncPipe, CurrencyPipe } from '@angular/common';
import { combineLatest, map } from 'rxjs';
import { TranslatePipe } from '@ngx-translate/core';
import { ConfiguratorService } from 'src/services/configurator.service';
import { ConfigurePricePipe } from '../../utils/configure-price.pipe';
import { ProductConfiguratorComponent } from '../product-configurator/product-configurator.component';

@Component({
    selector: 'app-product-details',
    templateUrl: './product-details.component.html',
    styleUrl: './product-details.component.scss',
    changeDetection: ChangeDetectionStrategy.OnPush,
    standalone: true,
    imports: [AsyncPipe, CurrencyPipe, TranslatePipe, ConfigurePricePipe, ProductConfiguratorComponent],
})
export class ProductDetailsComponent {
    constructor(private configuratorService: ConfiguratorService) {}

    @ViewChild('configurator') configurator: ProductConfiguratorComponent;

    data$ = combineLatest([this.configuratorService.configurator$, this.configuratorService.productData$]).pipe(
        map(([configurator, productData]) => ({ configurator, product: productData })),
    );

    onSubmit(): void {
        this.configurator.sendMetaData$.next();
    }
}
