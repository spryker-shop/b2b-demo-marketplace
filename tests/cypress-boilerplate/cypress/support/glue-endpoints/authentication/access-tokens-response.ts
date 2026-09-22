const accessTokenSchema = {
  type: 'object',
  properties: {
    data: {
      type: 'object',
      properties: {
        type: { type: 'string', enum: ['access-tokens'] },
        id: { type: ['string', 'null'] },
        attributes: {
          type: 'object',
          properties: {
            tokenType: { type: 'string', enum: ['Bearer'] },
            expiresIn: { type: 'number' },
            accessToken: { type: 'string' },
            refreshToken: { type: 'string' },
            idCompanyUser: { type: 'string' },
          },
          required: [
            'tokenType',
            'expiresIn',
            'accessToken',
            'refreshToken',
            'idCompanyUser',
          ],
        },
        links: {
          type: 'object',
          properties: {
            self: { type: 'string' },
          },
          required: ['self'],
        },
      },
      required: ['type', 'attributes', 'links'],
    },
  },
  required: ['data'],
}

export default accessTokenSchema
