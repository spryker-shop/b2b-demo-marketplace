export interface McpCommerceColdStartStaticFixtures {
  defaultPassword: string
  customer: {
    email: string
    reference: string
  }
  redirectUri: string
  searchTerm: string
  concreteSku: string
  quantityClearingMinimumOrder: number
  expectedToolNames: Array<string>
  protocolVersion: string
}

export interface PkcePair {
  codeVerifier: string
  codeChallenge: string
}
