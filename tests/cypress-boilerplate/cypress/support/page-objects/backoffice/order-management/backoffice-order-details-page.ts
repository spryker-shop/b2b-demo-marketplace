import { AbstractPage } from '../../abstract-page'

const omsTriggerForm = 'form[name="oms_trigger_form"]'

export class BackofficeOrderDetailsPage extends AbstractPage {
  protected PAGE_URL = ''

  openOrderDetails = (orderId: number): Cypress.Chainable => {
    this.PAGE_URL =
      Cypress.env('BACK_OFFICE_URL') + '/sales/detail?id-sales-order=' + orderId
    return cy.visit(this.PAGE_URL)
  }

  private getOrderOverview = (): Cypress.Chainable => {
    return cy.get('#order-overview')
  }

  getOrderGrandTotals = (): Cypress.Chainable => {
    return this.getOrderOverview().find('.grandTotal-row')
  }

  getOrderSubtotal = (): Cypress.Chainable => {
    return this.getOrderOverview().find('.subtotal-row')
  }

  getOmsTriggers = (
    options?: Partial<Cypress.Timeoutable>
  ): Cypress.Chainable => {
    return cy.get(omsTriggerForm, options)
  }

  private getOrderFlashMessages = (): Cypress.Chainable => {
    return cy.get('.flash-messages')
  }

  getSuccessfulOrderMessages = (): Cypress.Chainable => {
    return this.getOrderFlashMessages().find('.alert-info')
  }

  /**
   * Reads whatever OMS trigger forms the order details page currently has, tolerating none.
   *
   * `getOmsTriggers()` uses `cy.get()`, which fails when nothing matches — and an order can
   * legitimately reach a state that offers no events at all (for example once it has been sent
   * to the merchant). Waiting for the order overview first makes sure the page has rendered,
   * so an empty result means "no triggers" rather than "not loaded yet".
   */
  private withOmsTriggers = (
    callback: ($triggers: JQuery<HTMLElement>) => void
  ): void => {
    cy.get('#order-overview', { timeout: 20000 }).should('exist')
    cy.get('body').then(($body) => {
      callback($body.find(omsTriggerForm))
    })
  }

  private isTriggerOffered = (
    $triggers: JQuery<HTMLElement>,
    triggerName: string
  ): boolean => {
    return $triggers
      .find('button')
      .toArray()
      .some(
        (button) =>
          button.innerText.trim().toLowerCase() ===
          triggerName.trim().toLowerCase()
      )
  }

  /**
   * Clicks an OMS trigger and makes sure the transition really happened.
   *
   * The click is retried because Spryker takes a *non-blocking* lock before running a
   * transition (Oms\Business\Lock\TriggerLocker::acquire with $blocking = false). While an
   * `oms:check-condition` run holds that lock — which these specs provoke by calling
   * triggerOmsTransition() shortly before — a manual trigger is dropped silently: the page
   * still reports "Status change triggered successfully" while the item never leaves its
   * state. The trigger still being offered afterwards is the only reliable signal, so it
   * drives a retry instead of failing on the first attempt.
   */
  triggerOms = (triggerName: string, maxAttempts = 3): void => {
    const attemptClick = (attempt: number): void => {
      this.getOmsTriggers()
        .contains(triggerName, { timeout: 10000 })
        .should('be.visible')
        .click()

      this.withOmsTriggers(($triggers) => {
        if (!this.isTriggerOffered($triggers, triggerName)) {
          return
        }

        if (attempt >= maxAttempts) {
          throw new Error(
            `Trigger "${triggerName}" is still offered after ${maxAttempts} attempt(s) — the OMS transition never took effect. This usually means it was rejected because another OMS run held the state-machine lock.`
          )
        }

        cy.log(
          `Trigger "${triggerName}" still offered — the OMS lock likely rejected it. Retrying [${attempt}/${maxAttempts}]`
        )
        cy.wait(3000)
        cy.reload()
        attemptClick(attempt + 1)
      })
    }

    attemptClick(1)
  }

  /**
   * Clicks an OMS trigger only if the order details page currently offers it.
   *
   * Order state is also advanced by the environment (a scheduler, or the `oms:check-condition`
   * runs these specs perform), so an order can pass through — and out of — a state before the
   * test looks at it. Waiting for a transient trigger would then never return, so anything the
   * environment already did for us is simply skipped.
   */
  clickOmsTriggerIfOffered = (triggerName: string, maxAttempts = 3): void => {
    this.withOmsTriggers(($triggers) => {
      if (this.isTriggerOffered($triggers, triggerName)) {
        this.triggerOms(triggerName, maxAttempts)

        return
      }

      cy.log(
        `OMS trigger "${triggerName}" is not offered — the order has already advanced past it.`
      )
    })
  }

  /**
   * Waits until an item's state history contains the given state, reloading in between.
   * Asserting on the history rather than on a live trigger/state makes the check independent
   * of how fast the environment moved the order along.
   */
  waitForOrderItemStateInHistory = (
    concreteSku: string,
    stateName: string,
    maxRetries = 20
  ): void => {
    const check = (attempt: number): void => {
      this.getOrderItemHistory(concreteSku).then(($history) => {
        if ($history.text().toLowerCase().includes(stateName.toLowerCase())) {
          return
        }

        if (attempt >= maxRetries) {
          throw new Error(
            `State "${stateName}" never appeared in the state history of item "${concreteSku}" after ${maxRetries} reload(s).`
          )
        }

        cy.wait(5000)
        cy.reload()
        check(attempt + 1)
      })
    }

    check(1)
  }

  private getOrderItems = (): Cypress.Chainable => {
    return cy.get('[data-qa="order-item-list"]')
  }

  getOrderItemBySku = (concreteSku: string): Cypress.Chainable => {
    return this.getOrderItems().contains('.sku', concreteSku).parents('tr')
  }

  getOrderItemHistory = (concreteSku: string): Cypress.Chainable => {
    return this.getOrderItemBySku(concreteSku).find('.state-history')
  }

  getOrderItemTotal = (concreteSku: string): Cypress.Chainable => {
    return this.getOrderItemBySku(concreteSku).find(
      '[data-qa="item-total-amount"]'
    )
  }

  getCustomerEmail = (): Cypress.Chainable => {
    return cy.get('#customer dt:contains("Email") + dd')
  }
}
