/**
 * Entity types returned by the DynamicFixtures API that are used by more than one
 * application. One file per entity; a spec never imports these directly — they are the
 * building blocks of the per-feature fixture interfaces in the sibling `<app>/` folders.
 *
 * An entity used by only one application lives in that application's folder instead, so
 * everything here is genuinely shared.
 */
export * from './customer'
export * from './localized-attributes'
export * from './price-product'
export * from './product'
export * from './product-offer'
export * from './user'
