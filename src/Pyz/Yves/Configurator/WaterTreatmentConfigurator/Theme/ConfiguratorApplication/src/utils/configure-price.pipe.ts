import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
    name: 'configurePrice',
})
/* tslint:disable:no-magic-numbers */
export class ConfigurePricePipe implements PipeTransform {
    transform(price: number): number {
        return price / 100;
    }
}
/* tslint:enable:no-magic-numbers */
