import {
  CustomerFixture,
  PriceProductFixture,
  ProductFixture,
  ProductOfferFixture,
  UserFixture,
} from '../shared'

export interface MerchantOrderDynamicFixtures {
  backofficeUser: UserFixture
  merchantUser: UserFixture
  customer: CustomerFixture
  product: ProductFixture
  productPrice: PriceProductFixture
  productOffer: ProductOfferFixture
}

export interface MerchantOrderStaticFixtures {
  defaultPassword: string
}
