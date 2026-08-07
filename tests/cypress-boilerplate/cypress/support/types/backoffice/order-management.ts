import {
  CustomerFixture,
  PriceProductFixture,
  ProductFixture,
  ProductOfferFixture,
  UserFixture,
} from '../shared'

/**
 * Shared by both backoffice order specs — the cy-command flavour
 * (`backoffice-process-order`) and the scenario flavour
 * (`backoffice-process-order-using-scenario`), which need identical fixtures.
 */
export interface BackofficeOrderDynamicFixtures {
  backofficeUser: UserFixture
  customer: CustomerFixture
  product: ProductFixture
  productPrice: PriceProductFixture
  productOffer: ProductOfferFixture
}

export interface BackofficeOrderStaticFixtures {
  defaultPassword: string
}
