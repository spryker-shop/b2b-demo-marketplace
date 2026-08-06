import { CustomerFixture, ProductFixture } from '../shared'
import { MerchantFixture } from './merchant'

export interface QuickOrderDynamicFixtures {
  customer: CustomerFixture
  product: ProductFixture
  merchant: MerchantFixture
}

export interface QuickOrderStaticFixtures {
  defaultPassword: string
  quantity: number
}
