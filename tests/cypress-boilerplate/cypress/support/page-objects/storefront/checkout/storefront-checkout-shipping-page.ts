import { AbstractPage } from '../../abstract-page'

const shipmentForm = 'form[name="shipmentCollectionForm"]'

export class StorefrontCheckoutShippingPage extends AbstractPage {
  protected PAGE_URL = Cypress.env('STOREFRONT_URL') + '/en/checkout/shipment'

  selectShipment = (shipmentName: string): void => {
    cy.get('.shipment-sidebar').contains(shipmentName).click()
  }

  submitShipment = (): void => {
    cy.get(shipmentForm).submit()
  }

  provideShipment = (shipmentName: string): void => {
    this.selectShipment(shipmentName)
    this.submitShipment()
  }
}
