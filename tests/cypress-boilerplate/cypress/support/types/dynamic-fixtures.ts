// Shapes of the records the DynamicFixtures API returns. The API serialises Spryker
// transfer objects, so the field names are snake_case — these interfaces cover only the
// fields the specs actually read, not the full transfers.

export interface CustomerFixture {
  id_customer: number
  email: string
  customer_reference: string
  first_name: string
  last_name: string
}

export interface LocalizedAttributesFixture {
  name: string
  locale: { locale_name: string }
}

export interface ProductFixture {
  id_product_concrete: number
  fk_product_abstract: number
  sku: string
  abstract_sku: string
  localized_attributes: LocalizedAttributesFixture[]
}

export interface PriceProductFixture {
  money_value: {
    net_amount: number
    gross_amount: number
  }
}

export interface CompanyBusinessUnitFixture {
  id_company_business_unit: number
  name: string
}

export interface CostCenterFixture {
  id_cost_center: number
  name: string
}

export interface BudgetFixture {
  id_budget: number
  name: string
  amount: number
}

export interface ShipmentMethodFixture {
  id_shipment_method: number
  name: string
}

/**
 * Reads the fixtures the global `before` hook in support/e2e.ts loaded for the current
 * spec. Specs declare what their own fixture files produce:
 *
 * @example
 * const { dynamicFixtures, staticFixtures } = getFixtures<CartSmokeDynamicFixtures, CartSmokeStaticFixtures>()
 */
export const getFixtures = <TDynamic, TStatic>(): {
  dynamicFixtures: TDynamic
  staticFixtures: TStatic
} => {
  return {
    dynamicFixtures: Cypress.env('dynamicFixtures') as TDynamic,
    staticFixtures: Cypress.env('staticFixtures') as TStatic,
  }
}

/**
 * Returns the localized product name for the locale the storefront page objects browse,
 * so specs don't have to index into `localized_attributes` themselves.
 */
export const getProductName = (product: ProductFixture): string => {
  const localeName = Cypress.env('LOCALE_NAME')
  const localizedAttributes = product.localized_attributes.find(
    (attributes) => attributes.locale.locale_name === localeName
  )

  if (!localizedAttributes) {
    throw new Error(
      `Product ${product.abstract_sku} has no localized attributes for locale ${localeName}.`
    )
  }

  return localizedAttributes.name
}
