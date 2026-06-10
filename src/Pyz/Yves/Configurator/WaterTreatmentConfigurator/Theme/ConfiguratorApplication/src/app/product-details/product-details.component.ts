import { ChangeDetectionStrategy, Component, ViewChild } from '@angular/core';
import { combineLatest, map } from 'rxjs';
import { ConfiguratorService } from 'src/services/configurator.service';
import { ProductConfiguratorComponent } from '../product-configurator/product-configurator.component';

@Component({
    selector: 'app-product-details',
    templateUrl: './product-details.component.html',
    styleUrls: ['./product-details.component.scss'],
    changeDetection: ChangeDetectionStrategy.OnPush,
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
