import { getFixtures } from '@support/fixture-helper/fixture-helper'
import {
  McpCommerceColdStartStaticFixtures,
  PkcePair,
} from '@support/types/glue'

/**
 * PRD §4.1 end-to-end requirement: the WHOLE cold-start journey in a single run —
 * 401 -> discovery -> registration -> PKCE authorize -> token -> initialize -> search ->
 * add to cart -> checkout -> order visible.
 *
 * The MCP surface is pure HTTP: OAuth form posts, a server-rendered consent screen and JSON-RPC over
 * `POST /mcp`. There is no storefront UI to drive, so the journey is executed with `cy.request()`
 * rather than page objects. That is deliberate — driving it through a UI would test nothing the
 * protocol actually exposes.
 *
 * The customer is `DE--2`. `spencor.hopkin@acme.com` cannot be used here: a B2B purchasing-limit
 * permission refuses order placement, and the shop enforces a EUR 40 minimum order — hence the
 * quantity in the fixture. Products are addressed by SKU only, never by an import-order-dependent
 * id (PRD §4.4).
 *
 * URLs are relative: cypress.config.ts sets `baseUrl` from GLUE_URL.
 */
describe('MCP Commerce Server - cold-start purchase journey', (): void => {
  let staticFixtures: McpCommerceColdStartStaticFixtures

  before((): void => {
    ;({ staticFixtures } = getFixtures<
      unknown,
      McpCommerceColdStartStaticFixtures
    >())
  })

  it('Positive | Completes the whole chain with no pre-existing credentials', (): void => {
    const state = `cypress-${Date.now()}`

    // 1) A cold client has no credential. The 401 must point at the protected-resource metadata,
    //    which is the only thing it knows to look for.
    cy.request({
      method: 'POST',
      url: '/mcp',
      failOnStatusCode: false,
      body: { jsonrpc: '2.0', id: 1, method: 'tools/list' },
    }).then((response): void => {
      expect(response.status, 'unauthenticated /mcp call is refused').to.eq(401)
      expect(
        response.headers['www-authenticate'],
        'WWW-Authenticate names the metadata document'
      ).to.include('/.well-known/oauth-protected-resource')
      expect(
        JSON.stringify(response.body),
        'no tool is listed to an anonymous caller'
      ).to.not.include('product_search')
    })

    // 2) Follow that pointer to the protected-resource document, then to the authorization server.
    cy.request('/.well-known/oauth-protected-resource').then(
      (response): void => {
        expect(response.status).to.eq(200)
        expect(response.body.resource).to.contain('/mcp')
        expect(response.body.authorization_servers).to.have.length(1)
      }
    )

    cy.request('/.well-known/oauth-authorization-server')
      .then((response): void => {
        expect(response.status).to.eq(200)
        expect(response.body.grant_types_supported).to.deep.eq([
          'authorization_code',
        ])
        expect(
          response.body.code_challenge_methods_supported,
          'PKCE S256 is mandatory'
        ).to.deep.eq(['S256'])
        expect(response.body.registration_endpoint).to.contain('/register')
      })

      // 3) Register with no pre-provisioning at all.
      .then(
        (): Cypress.Chainable<Cypress.Response<{ client_id: string }>> =>
          cy.request({
            method: 'POST',
            url: '/register',
            body: {
              client_name: 'Cypress MCP client',
              redirect_uris: [staticFixtures.redirectUri],
            },
          })
      )
      .then(
        (
          registrationResponse
        ): Cypress.Chainable<PkcePair & { clientId: string }> => {
          expect(registrationResponse.status).to.eq(201)
          expect(registrationResponse.body.client_id).to.be.a('string').and.not
            .be.empty

          const clientId = registrationResponse.body.client_id

          // 4) Generate a real S256 PKCE pair, so the challenge is never precomputed.
          return cy
            .task<PkcePair>('createPkcePair')
            .then((pkcePair): PkcePair & { clientId: string } => ({
              ...pkcePair,
              clientId,
            }))
        }
      )
      .then(
        ({
          clientId,
          codeVerifier,
          codeChallenge,
        }): Cypress.Chainable<string> =>
          // 5) Authorize: the customer signs in and approves. The code arrives on the redirect.
          cy
            .request({
              method: 'POST',
              url: '/authorize',
              form: true,
              followRedirect: false,
              failOnStatusCode: false,
              body: {
                response_type: 'code',
                client_id: clientId,
                redirect_uri: staticFixtures.redirectUri,
                code_challenge: codeChallenge,
                code_challenge_method: 'S256',
                state,
                email: staticFixtures.customer.email,
                password: staticFixtures.defaultPassword,
                approve: 'yes',
              },
            })
            .then((authorizeResponse): Cypress.Chainable<string> => {
              expect(
                authorizeResponse.status,
                'approval redirects back to the client'
              ).to.eq(302)

              const location = String(authorizeResponse.headers.location)
              const redirectUrl = new URL(location)
              const code = redirectUrl.searchParams.get('code')

              expect(code, 'an authorization code is issued').to.be.a('string')
                .and.not.be.empty
              expect(
                redirectUrl.searchParams.get('state'),
                'state is echoed unchanged'
              ).to.eq(state)
              expect(
                location,
                'the redirect carries no shop token'
              ).to.not.include('eyJ')

              // 6) Exchange the code for an MCP token. The verifier proves the same client.
              return cy
                .request({
                  method: 'POST',
                  url: '/token',
                  form: true,
                  body: {
                    grant_type: 'authorization_code',
                    code,
                    client_id: clientId,
                    redirect_uri: staticFixtures.redirectUri,
                    code_verifier: codeVerifier,
                  },
                })
                .then((tokenResponse): Cypress.Chainable<string> => {
                  expect(tokenResponse.status).to.eq(200)
                  expect(tokenResponse.body.token_type).to.eq('Bearer')

                  const accessToken = String(tokenResponse.body.access_token)

                  expect(accessToken, 'an opaque MCP credential is issued').to
                    .not.be.empty

                  // PRD Goal 3: the shop's own tokens must not be handed to the AI client.
                  const rawTokenBody = JSON.stringify(tokenResponse.body)
                  expect(
                    rawTokenBody,
                    'no shop JWT in the token response'
                  ).to.not.include('eyJ')
                  expect(
                    rawTokenBody,
                    'no refresh token in the token response'
                  ).to.not.include('refresh_token')

                  return cy.wrap(accessToken)
                })
            })
      )
      .then((accessToken): void => {
        const callMcp = (
          method: string,
          params?: Record<string, unknown>
        ): Cypress.Chainable<
          Cypress.Response<{ result: Record<string, never> }>
        > =>
          cy.request({
            method: 'POST',
            url: '/mcp',
            headers: { Authorization: `Bearer ${accessToken}` },
            body: {
              jsonrpc: '2.0',
              id: 1,
              method,
              ...(params ? { params } : {}),
            },
          })

        const callTool = (
          name: string,
          args: Record<string, unknown>
        ): Cypress.Chainable<
          Cypress.Response<{ result: Record<string, never> }>
        > => callMcp('tools/call', { name, arguments: args })

        // 7) Open the session and confirm the negotiated protocol plus the advertised tool surface.
        callMcp('initialize', {
          protocolVersion: staticFixtures.protocolVersion,
        }).then((response): void => {
          expect(response.status).to.eq(200)
          expect(response.body.result.protocolVersion).to.eq(
            staticFixtures.protocolVersion
          )
          expect(response.body.result.capabilities).to.have.property('tools')
        })

        callMcp('tools/list').then((response): void => {
          const toolNames = (
            response.body.result.tools as Array<{ name: string }>
          ).map((tool): string => tool.name)

          expect(toolNames.sort()).to.deep.eq(
            [...staticFixtures.expectedToolNames].sort()
          )
        })

        // 8) Find the product by description, and confirm the SKU the search reports is usable.
        callTool('product_search', { query: staticFixtures.searchTerm }).then(
          (response): void => {
            const content = response.body.result.structuredContent as {
              products: Array<{ sku: string; name: string; price: number }>
            }

            expect(
              response.body.result.isError,
              'a search is never an error'
            ).to.eq(false)
            expect(content.products.length, 'at most 12 products').to.be.within(
              1,
              12
            )
            content.products.forEach((product): void => {
              expect(product.sku).to.not.be.empty
              expect(product.name).to.not.be.empty
              expect(product.price).to.not.be.null
            })

            cy.wrap(content.products[0].sku).as('searchReportedSku')
          }
        )

        cy.get('@searchReportedSku').then((searchReportedSku): void => {
          callTool('product_detail', { sku: String(searchReportedSku) }).then(
            (response): void => {
              const detail = response.body.result.structuredContent as {
                name: string
                price: number
              }

              expect(
                response.body.result.isError,
                'the SKU a search reports must resolve to detail'
              ).to.eq(false)
              expect(detail.name).to.not.be.empty
              expect(detail.price).to.not.be.null
            }
          )
        })

        // 9) Build the cart. Quantity clears the EUR 40 minimum-order threshold.
        callTool('add_to_cart', {
          sku: staticFixtures.concreteSku,
          quantity: staticFixtures.quantityClearingMinimumOrder,
        }).then((response): void => {
          const cart = response.body.result.structuredContent as {
            cartId: string
            items: Array<{ sku: string; quantity: number }>
            cartTotal: number
          }

          expect(response.body.result.isError).to.eq(false)
          expect(cart.cartId, 'the cart identifier is returned').to.not.be.empty
          expect(
            cart.cartTotal,
            'the cart total is returned'
          ).to.be.greaterThan(0)

          const addedItem = cart.items.find(
            (item): boolean => item.sku === staticFixtures.concreteSku
          )
          expect(addedItem, 'the requested SKU is on the cart').to.not.be
            .undefined
          expect(addedItem?.quantity).to.be.gte(
            staticFixtures.quantityClearingMinimumOrder
          )

          cy.wrap(cart.cartId).as('cartId')
        })

        // 10) Place the order, then confirm the customer can see it in their history.
        cy.get('@cartId').then((cartId): void => {
          callTool('checkout', { cartId: String(cartId) }).then(
            (response): void => {
              expect(
                response.body.result.isError,
                'checkout succeeds for a filled cart'
              ).to.eq(false)

              const orderReference = String(
                (
                  response.body.result.structuredContent as {
                    orderReference: string
                  }
                ).orderReference
              )

              expect(orderReference, 'an order reference comes back').to.not.be
                .empty
              expect(
                JSON.stringify(response.body),
                'no shop token in the checkout response'
              ).to.not.include('eyJ')

              cy.wrap(orderReference).as('orderReference')
            }
          )
        })

        cy.get('@orderReference').then((orderReference): void => {
          callTool('order_list', {}).then((response): void => {
            const orderList = response.body.result.structuredContent as {
              orderCount: number
              orders: Array<{
                orderReference: string
                total: number
                currency: string
              }>
            }

            expect(response.body.result.isError).to.eq(false)
            expect(orderList.orderCount).to.be.greaterThan(0)

            const placedOrder = orderList.orders.find(
              (order): boolean =>
                order.orderReference === String(orderReference)
            )

            expect(
              placedOrder,
              'the order just placed is visible in the history'
            ).to.not.be.undefined
            expect(placedOrder?.total).to.be.greaterThan(0)
            expect(placedOrder?.currency).to.eq('EUR')
          })
        })
      })
  })
})
