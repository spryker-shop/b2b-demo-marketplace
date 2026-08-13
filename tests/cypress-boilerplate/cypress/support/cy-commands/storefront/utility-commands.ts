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
  (price: number, locale?: string): Cypress.Chainable => {
    const intlLocale = (locale ?? String(Cypress.env('LOCALE_NAME'))).replace(
      '_',
      '-'
    )
    const currency = String(Cypress.env('CURRENCY_CODE'))

    const formattedPrice = new Intl.NumberFormat(intlLocale, {
      style: 'currency',
      currency,
    }).format(price / 100)

    return cy.wrap(formattedPrice)
  }
)
