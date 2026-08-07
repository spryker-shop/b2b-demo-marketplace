import { GlueRequest } from '../glue-request'

export class CartsItems extends GlueRequest {
  protected ENDPOINT_NAME = ''

  private setEndpoint(cartId: string): void {
    this.ENDPOINT_NAME = `/carts/${cartId}/items`
  }

  addItemToCart = (
    token: string,
    cartId: string,
    sku: string,
    qty: number
  ): Cypress.Chainable => {
    this.setEndpoint(cartId)

    return cy.api({
      method: 'POST',
      url: this.GLUE_ENDPOINT,
      headers: {
        Authorization: 'Bearer ' + token,
      },
      body: {
        data: {
          type: 'items',
          attributes: {
            sku: sku,
            quantity: qty,
          },
        },
      },
      failOnStatusCode: false,
    })
  }

  addOfferToCart = (
    token: string,
    cartId: string,
    sku: string,
    qty: number,
    offer: string,
    merchant: string
  ): Cypress.Chainable => {
    this.setEndpoint(cartId)

    return cy.api({
      method: 'POST',
      url: this.GLUE_ENDPOINT,
      headers: {
        Authorization: 'Bearer ' + token,
      },
      body: {
        data: {
          type: 'items',
          attributes: {
            sku: sku,
            quantity: qty,
            productOfferReference: offer,
            merchantReference: merchant,
          },
        },
      },
      failOnStatusCode: false,
    })
  }
}
