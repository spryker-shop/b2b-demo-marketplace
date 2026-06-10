import { ChangeDetectionStrategy, Component } from '@angular/core';
import { ConfiguratorService } from '../../services/configurator.service';
import { ProductService } from '../../services/product.service';

@Component({
    selector: 'app-json-output',
    templateUrl: './json-output.component.html',
    styleUrls: ['./json-output.component.scss'],
    changeDetection: ChangeDetectionStrategy.OnPush,
})
export class JsonOutputComponent {
    constructor(private productService: ProductService, private configuration: ConfiguratorService) {}

    data$ = this.productService.getData();
    configurator$ = this.configuration.configurator$;
}
