import { Pipe, PipeTransform } from '@angular/core';

@Pipe({
    name: 'configurePrice',
    standalone: true,
})
export class ConfigurePricePipe implements PipeTransform {
    transform(price: number): number {
        return price / 100;
    }
}
