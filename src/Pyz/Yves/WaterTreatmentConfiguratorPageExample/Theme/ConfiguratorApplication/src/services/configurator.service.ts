import { HttpClient } from '@angular/common/http';
import { Injectable } from '@angular/core';
import {
    BehaviorSubject,
    Observable,
    combineLatest,
    map,
    merge,
    of,
    scan,
    shareReplay,
    startWith,
    switchMap,
    withLatestFrom,
} from 'rxjs';
import { environment } from '../environments/environment';
import { ProductService } from './product.service';
import { ConfiguredProduct, CurrencyPrices, MockConfigurator, MockDataItem, MockVolumePricesConfig, ServerData } from './types';

const FALLBACK_CURRENCY = 'EUR';

export const ASSETS = !environment.production ? '' : './dist';
const CONFIGURATOR = !environment.production ? `${ASSETS}/assets/data/configurator.json` : './configurator.json';

@Injectable({ providedIn: 'root' })
export class ConfiguratorService {
    constructor(private http: HttpClient, private product: ProductService) {}

    configuration$ = this.http.get<MockConfigurator>(CONFIGURATOR).pipe(shareReplay({ bufferSize: 1, refCount: true }));

    data$ = this.configuration$.pipe(
        map((data) => data.configuration),
        shareReplay({ bufferSize: 1, refCount: true }),
    );
    defaults$ = this.configuration$.pipe(map((data) => data.defaults));
    productData$ = this.configuration$.pipe(
        withLatestFrom(this.product.getData()),
        map(([data, product]) => ({
            ...data.data,
            ...product,
        })),
        shareReplay({ bufferSize: 1, refCount: true }),
    );

    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    setConfigurator$ = new BehaviorSubject<Partial<Record<string, any>>>({});

    configurator$: Observable<ConfiguredProduct & { price: number }> = this.product.getData().pipe(
        switchMap((_data: ServerData) => {
            const data = this.generateConfiguredData(_data);
            return this.setConfigurator$.pipe(
                startWith(data),
                scan((config, newConfig) => ({
                    ...config,
                    ...newConfig,
                    configuration: {
                        ...config.configuration,
                        ...newConfig.configuration,
                    },
                })),
                switchMap((configurator) => combineLatest([of(configurator), this.configuration$])),
                map(([configurator, config]) => {
                    const {
                        defaults,
                        configuration: data,
                        data: { defaultPrice },
                        volumePrices,
                    } = config;

                    const currency = configurator.currency_code;

                    configurator.configuration = {
                        ...defaults,
                        // eslint-disable-next-line @typescript-eslint/no-explicit-any
                        ...(configurator.configuration as Record<string, any>),
                    };
                    configurator.display_data = {};
                    configurator.available_quantity = null;

                    this.assignVolumePrices(configurator, volumePrices);

                    configurator.price = Object.entries(configurator.configuration).reduce((price, [key, value]) => {
                        const config = data.find((configs) => configs.id === key);
                        const active = config?.data?.find((options) => options.value === value);
                        const uncheck = active?.disabled
                            ? Object.entries(active.disabled).some(([key, value]) =>
                                  (value.condition as string[]).includes(configurator.configuration[key]),
                              )
                            : null;
                        this.assignAvailability(active, configurator);

                        if (uncheck) {
                            delete configurator.configuration[key];
                            delete configurator.display_data[config.label];
                        }

                        if (active && !uncheck) {
                            configurator.display_data[config.label] = active.title;
                        }

                        return active && !uncheck ? this.resolvePrice(active.price, currency) + price : price;
                    }, this.resolvePrice(defaultPrice, currency));

                    return { ...configurator } as ConfiguredProduct & { price: number };
                }),
            );
        }),
        shareReplay({ bufferSize: 1, refCount: true }),
    );
    loading$ = merge(this.product.getData().pipe(map(() => true)), this.configurator$.pipe(map(() => false))).pipe(
        startWith(true),
    );
    dirty$ = combineLatest([this.configurator$, this.defaults$]).pipe(
        map(([data, defaults]) => JSON.stringify(data.configuration) !== JSON.stringify(defaults)),
    );

    private generateConfiguredData(response: ServerData): ConfiguredProduct {
        const configuration = response.configuration.length ? JSON.parse(response.configuration) : {};
        const displayData = response.display_data.length ? JSON.parse(response.display_data) : {};
        const productData = {
            ...response,
            ...({
                configuration,
                display_data: displayData,
            } as ConfiguredProduct),
        };

        return {
            ...productData,
            price: 0,
        };
    }

    updateConfiguratorConfiguration(data: Record<string, unknown>) {
        this.setConfigurator$.next({
            configuration: data,
        });
    }

    updateWithGeneratedProductData(newProductData): void {
        this.setConfigurator$.next(newProductData);
    }

    remove(propertyName: string, productData: ConfiguredProduct): void {
        delete productData[propertyName];

        this.updateWithGeneratedProductData(productData);
    }

    /**
     * Resolves a per-currency price map to a single amount for the active currency.
     * Falls back to EUR, then to the first available currency, then to 0.
     */
    private resolvePrice(prices: CurrencyPrices | undefined, currency: string): number {
        if (!prices) {
            return 0;
        }

        return prices[currency] ?? prices[FALLBACK_CURRENCY] ?? Object.values(prices)[0] ?? 0;
    }

    private assignAvailability(item: MockDataItem | undefined, configurator: Partial<ConfiguredProduct>): void {
        if (item?.availableQuantity === undefined) {
            return;
        }

        const availableQuantity =
            typeof item.availableQuantity === 'number'
                ? item.availableQuantity
                : item.availableQuantity.find((condition) =>
                      Object.entries(condition.condition).every(([key, value]) => configurator[key] === value),
                  )?.quantity;

        if (configurator.available_quantity === null || availableQuantity < configurator.available_quantity) {
            configurator.available_quantity = availableQuantity;
        }
    }

    private assignVolumePrices(
        configurator: Partial<ConfiguredProduct>,
        volumePrices?: MockVolumePricesConfig[],
    ): void {
        if (!volumePrices) {
            configurator.volume_prices = null;

            return;
        }

        for (const volumePrice of volumePrices) {
            if (this.areObjectsEqual(volumePrice.condition, configurator.configuration)) {
                configurator.volume_prices = {
                    volume_prices: volumePrice.prices[configurator.price_mode],
                };

                return;
            }
        }

        configurator.volume_prices = null;
    }

    private areObjectsEqual(obj1: Record<string, string>, obj2: Record<string, string>): boolean {
        const sortedKeys1 = Object.keys(obj1).sort();
        const sortedKeys2 = Object.keys(obj2).sort();

        if (
            sortedKeys1.length !== sortedKeys2.length ||
            !sortedKeys1.every((key, index) => key === sortedKeys2[index])
        ) {
            return false;
        }

        return sortedKeys1.every((key) => obj1[key] === obj2[key]);
    }
}
