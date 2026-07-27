Cypress.Commands.add('closeAllFlashMessages', (): Cypress.Chainable => {
  return cy
    .get('flash-message')
    .filter(':visible')
    .each(($flash) => {
      // Check if the flash message is still connected to the DOM (that it did not disappear on its own)
      if (Cypress.dom.isAttached($flash)) {
        cy.wrap($flash).click()
      }
    })
})

Cypress.Commands.add(
  'formatDisplayPrice',
  (price: number): Cypress.Chainable => {
    const priceInEuros = (price / 100).toFixed(2)
    const formattedPrice = `€${parseFloat(priceInEuros).toLocaleString('en-US', { minimumFractionDigits: 2 })}`

    return cy.wrap(formattedPrice)
  }
)
