import { CustomerFixture, PriceProductFixture, ProductFixture } from '../shared'
import { BudgetFixture } from './budget'
import { CostCenterFixture } from './cost-center'
import { ShipmentMethodFixture } from './shipment-method'

export interface CheckoutDynamicFixtures {
  customer: CustomerFixture
  product: ProductFixture
  productPrice: PriceProductFixture
  shipmentMethod: ShipmentMethodFixture
  costCenter: CostCenterFixture
  budget: BudgetFixture
}

export interface CheckoutStaticFixtures {
  defaultPassword: string
  paymentMethodKey: string
}
