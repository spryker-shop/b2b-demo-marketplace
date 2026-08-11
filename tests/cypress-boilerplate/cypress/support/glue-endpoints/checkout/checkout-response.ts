const checkoutSchema = {
  type: 'object',
  properties: {
    data: {
      type: 'object',
      properties: {
        type: { type: 'string', enum: ['checkout'] },
        id: { type: ['string', 'null'] },
        attributes: {
          type: 'object',
          properties: {
            orderReference: { type: 'string' },
            redirectUrl: { type: ['string', 'null'] },
            isExternalRedirect: { type: ['boolean', 'null'] },
          },
          required: ['orderReference', 'redirectUrl', 'isExternalRedirect'],
        },
        links: {
          type: 'object',
          properties: {
            self: { type: 'string' },
          },
          required: ['self'],
        },
        relationships: {
          type: 'object',
          properties: {
            orders: {
              type: 'object',
              properties: {
                data: {
                  type: 'array',
                  items: {
                    type: 'object',
                    properties: {
                      type: { type: 'string', enum: ['orders'] },
                      id: { type: 'string' },
                    },
                    required: ['type', 'id'],
                  },
                },
              },
              required: ['data'],
            },
          },
          required: ['orders'],
        },
      },
      required: ['type', 'attributes', 'links', 'relationships'],
    },
  },
  required: ['data'],
  additionalProperties: true, // Allows extra fields like "included" without validation failure
}

export default checkoutSchema
