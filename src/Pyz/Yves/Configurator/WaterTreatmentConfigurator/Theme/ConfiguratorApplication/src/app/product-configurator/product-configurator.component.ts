import { ChangeDetectionStrategy, Component, ElementRef, OnInit, ViewChild } from '@angular/core';
import { Subject } from 'rxjs';
import { delay, shareReplay, switchMap, takeUntil, tap } from 'rxjs/operators';
import { ProductService } from 'src/services/product.service';
import { ConfiguratorService } from '../../services/configurator.service';

@Component({
    selector: 'app-product-configurator',
    templateUrl: './product-configurator.component.html',
    changeDetection: ChangeDetectionStrategy.OnPush,
})
export class ProductConfiguratorComponent implements OnInit {
    constructor(private productService: ProductService, private configuratorService: ConfiguratorService) {}

    @ViewChild('form') form: ElementRef<HTMLFormElement>;

    productData$ = this.configuratorService.configurator$;
    data$ = this.configuratorService.data$;

    sendMetaData$ = new Subject<void>();
    metaData$ = this.sendMetaData$.pipe(
        switchMap(() => {
            const formData = new FormData(this.form.nativeElement);

            return this.productService.getMetaData(formData);
        }),
        shareReplay({ bufferSize: 1, refCount: true }),
    );

    destroy$ = new Subject<void>();
    submitForm$ = this.metaData$.pipe(
        takeUntil(this.destroy$),
        delay(0),
        tap(() => this.form.nativeElement.submit()),
    );

    ngOnInit(): void {
        this.submitForm$.subscribe();
    }

    onConfigurationChange(value: string, id: string) {
        this.configuratorService.updateConfiguratorConfiguration({
            [id]: value,
        });
    }
}
