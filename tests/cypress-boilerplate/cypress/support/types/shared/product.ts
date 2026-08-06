import { LocalizedAttributesFixture } from './localized-attributes'

export interface ProductFixture {
  id_product_concrete: number
  fk_product_abstract: number
  sku: string
  abstract_sku: string
  localized_attributes: LocalizedAttributesFixture[]
}
