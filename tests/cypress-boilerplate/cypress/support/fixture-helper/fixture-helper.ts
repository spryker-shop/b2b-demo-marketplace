import { ProductFixture } from '../types/shared'

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
