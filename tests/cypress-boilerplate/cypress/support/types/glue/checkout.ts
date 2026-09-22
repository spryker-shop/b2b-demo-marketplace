import { CustomerFixture, ProductFixture, ProductOfferFixture } from '../shared'

export interface GlueCheckoutDynamicFixtures {
  customer: CustomerFixture
  product: ProductFixture
  productOffer: ProductOfferFixture
}

export interface GlueCheckoutStaticFixtures {
  defaultPassword: string
}
