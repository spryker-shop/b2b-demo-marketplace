export abstract class GlueRequest {
  protected GLUE_DOMAIN = Cypress.env('GLUE_URL')
  protected abstract ENDPOINT_NAME: string

  protected get GLUE_ENDPOINT(): string {
    return `${this.GLUE_DOMAIN}${this.ENDPOINT_NAME}`
  }
}
