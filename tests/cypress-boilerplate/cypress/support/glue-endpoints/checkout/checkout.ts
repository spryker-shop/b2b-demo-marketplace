import { GlueRequest } from '../glue-request'

export class Checkout extends GlueRequest {
  protected ENDPOINT_NAME = '/checkout?include=orders'

  placeOrder = (
    token: string,
    cartId: string,
    email: string,
    shipment: number,
    paymentProvider: string,
    paymentMethod: string
  ): Cypress.Chainable => {
    return cy.api({
      method: 'POST',
      url: this.GLUE_ENDPOINT,
      headers: {
        Authorization: 'Bearer ' + token,
      },
      body: {
        data: {
          type: 'checkout',
          attributes: {
            customer: {
              salutation: 'Ms',
              email: email,
              firstName: 'Sonia',
              lastName: 'Wagner',
            },
            idCart: cartId,
            billingAddress: {
              salutation: 'Ms',
              email: email,
              firstName: 'Sonia',
              lastName: 'Wagner',
              address1: 'Seeburger Str.',
              address2: '120',
              address3: '',
              zipCode: '10115',
              city: 'Berlin',
              iso2Code: 'DE',
              company: 'Spryker',
              phone: '+123456789900',
              isDefaultShipping: true,
              isDefaultBilling: true,
            },
            shippingAddress: {
              salutation: 'Ms',
              email: email,
              firstName: 'Sonia',
              lastName: 'Wagner',
              address1: 'Seeburger Str.',
              address2: '210',
              address3: '',
              zipCode: '10115',
              city: 'Berlin',
              iso2Code: 'DE',
              company: 'Spryker',
              phone: '+990056789123',
              isDefaultShipping: true,
              isDefaultBilling: true,
            },
            payments: [
              {
                paymentMethodName: paymentMethod,
                paymentProviderName: paymentProvider,
              },
            ],
            shipment: {
              idShipmentMethod: shipment,
            },
          },
        },
      },
      failOnStatusCode: true,
    })
  }
}
