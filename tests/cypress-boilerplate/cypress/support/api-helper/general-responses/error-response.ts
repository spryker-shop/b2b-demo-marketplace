const errorResponseSchema = {
  type: 'object',
  properties: {
    errors: {
      type: 'array',
      items: {
        type: 'object',
        properties: {
          code: { type: 'string' },
          status: { type: 'number' },
          detail: { type: 'string' },
        },
        required: ['code', 'status', 'detail'],
      },
    },
  },
  required: ['errors'],
}

export default errorResponseSchema
