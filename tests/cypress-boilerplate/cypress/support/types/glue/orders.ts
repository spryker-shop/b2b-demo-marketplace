import { CustomerFixture, ProductFixture, ProductOfferFixture } from '../shared'

export interface GlueOrdersDynamicFixtures {
  customer: CustomerFixture
  product: ProductFixture
  productOffer: ProductOfferFixture
}

export interface GlueOrdersStaticFixtures {
  defaultPassword: string
}
