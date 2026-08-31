import { AbstractPage } from '../../abstract-page'

export class StorefrontHomePage extends AbstractPage {
  protected PAGE_URL =
    Cypress.env('STOREFRONT_URL') + '/' + Cypress.env('LOCALE_PREFIX')
}
