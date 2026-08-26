import { CustomerFixture, PriceProductFixture, ProductFixture } from '../shared'

export interface CartSmokeDynamicFixtures {
  customer: CustomerFixture
  product: ProductFixture
  productPrice: PriceProductFixture
}

export interface CartSmokeStaticFixtures {
  defaultPassword: string
}
