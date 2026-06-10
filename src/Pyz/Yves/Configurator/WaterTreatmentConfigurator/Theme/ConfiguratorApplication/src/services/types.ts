export interface VolumePrices {
    quantity: number;
    net_price: number;
    gross_price: number;
}

/**
 * Per-currency prices keyed by ISO currency code (e.g. EUR, CHF, USD).
 * Values are integer amounts in the currency's minor unit (cents).
 */
export type CurrencyPrices = Record<string, number>;

export interface MockDataItemDisabledRule {
    condition: string[];
    tooltip: string;
}

/**
 * Cross-blocking rules keyed by the group id whose selected value can disable this option.
 * The option becomes unavailable when the selected value of `<groupId>` is in `condition`.
 */
export type MockDataItemDisabled = Record<string, MockDataItemDisabledRule>;

export interface MockAvailabilityQuantity {
    condition: Partial<ProductData>;
    quantity: number;
}

export interface MockVolumePrices {
    condition: Partial<ProductData>;
    quantity: number;
}

export interface MockVolumePricesConfig {
    condition: Record<string, string>;
    prices: {
        GROSS_MODE: VolumePrices;
        NET_MODE: VolumePrices;
    };
}

export interface MockDataItem {
    value: string;
    title: string;
    price: CurrencyPrices;
    disabled?: MockDataItemDisabled;
    availableQuantity?: number | MockAvailabilityQuantity[];
}

export interface MockData {
    id: string;
    label: string;
    tooltip: string;
    icon: string;
    data: MockDataItem[];
}

export interface MockProductInfo {
    name: string;
    image: string;
    logo: string;
    defaultPrice?: CurrencyPrices;
}

export interface MockConfigurator {
    configuration: MockData[];
    data: MockProductInfo;
    defaults: Record<string, string>;
    volumePrices?: MockVolumePricesConfig[];
    debug?: boolean;
}

export interface ProductMetaData {
    timestamp?: string;
    checkSum?: string;
}

export interface ProductData {
    sku: string;
    item_group_key: string;
    quantity: number;
    configurator_key: string;
    customer_reference: string;
    store_name: string;
    currency_code: string;
    locale_name: string;
    price_mode: string;
    source_type: string;
    back_url: string;
    submit_url: string;
    id_wishlist_item: string;
    shopping_list_item_uuid: string;
}

export interface ServerData extends ProductData {
    configuration: string;
    display_data: string;
}

export interface ConfiguredProduct extends ProductData {
    price: number;
    configuration: Record<string, string>;
    display_data: Record<string, string>;
    available_quantity: number | null;
    volume_prices?: {
        volume_prices?: VolumePrices[];
    };
}
