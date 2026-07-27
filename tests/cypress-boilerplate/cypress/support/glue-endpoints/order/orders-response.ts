const ordersSchema = {
  type: 'object',
  properties: {
    data: {
      type: 'object',
      properties: {
        type: { type: 'string' },
        id: { type: 'string' },
        attributes: {
          type: 'object',
          properties: {
            merchantReferences: {
              type: 'array',
              items: { type: 'string' },
            },
            createdAt: { type: 'string' },
            totals: {
              type: 'object',
              properties: {
                expenseTotal: { type: 'number' },
                discountTotal: { type: 'number' },
                taxTotal: { type: 'number' },
                subtotal: { type: 'number' },
                grandTotal: { type: 'number' },
                canceledTotal: { type: 'number' },
                remunerationTotal: { type: 'number' },
              },
              required: [
                'expenseTotal',
                'discountTotal',
                'taxTotal',
                'subtotal',
                'grandTotal',
              ],
            },
            currencyIsoCode: { type: 'string' },
            items: {
              type: 'array',
              items: {
                type: 'object',
                properties: {
                  salesOrderConfiguredBundle: {
                    type: ['object', 'null'],
                    properties: {
                      idSalesOrderConfiguredBundle: { type: 'number' },
                      configurableBundleTemplateUuid: { type: 'string' },
                      name: { type: 'string' },
                      quantity: { type: 'number' },
                    },
                    required: [
                      'idSalesOrderConfiguredBundle',
                      'configurableBundleTemplateUuid',
                      'name',
                      'quantity',
                    ],
                  },
                  salesOrderConfiguredBundleItem: {
                    type: ['object', 'null'],
                    properties: {
                      idSalesOrderConfiguredBundle: { type: 'number' },
                      configurableBundleTemplateSlotUuid: { type: 'string' },
                    },
                    required: [
                      'idSalesOrderConfiguredBundle',
                      'configurableBundleTemplateSlotUuid',
                    ],
                  },
                  merchantReference: { type: 'string' },
                  name: { type: 'string' },
                  sku: { type: 'string' },
                  sumPrice: { type: 'number' },
                  quantity: { type: 'number' },
                  metadata: {
                    type: 'object',
                    properties: {
                      superAttributes: {
                        type: 'array',
                        items: { type: 'string' },
                      },
                      image: { type: 'string' },
                    },
                  },
                  calculatedDiscounts: {
                    type: 'array',
                    items: {
                      type: 'object',
                      properties: {
                        unitAmount: { type: 'number' },
                        sumAmount: { type: 'number' },
                        displayName: { type: ['string', 'null'] },
                        description: { type: ['string', 'null'] },
                        voucherCode: { type: ['string', 'null'] },
                        quantity: { type: 'number' },
                      },
                      required: [
                        'unitAmount',
                        'sumAmount',
                        'displayName',
                        'description',
                        'voucherCode',
                        'quantity',
                      ],
                    },
                  },
                  unitGrossPrice: { type: 'number' },
                  sumGrossPrice: { type: 'number' },
                  taxRate: { type: ['string', 'null'] },
                  unitNetPrice: { type: 'number' },
                  sumNetPrice: { type: 'number' },
                  unitPrice: { type: 'number' },
                  unitTaxAmountFullAggregation: { type: 'number' },
                  sumTaxAmountFullAggregation: { type: 'number' },
                  refundableAmount: { type: 'number' },
                  canceledAmount: { type: 'number' },
                  sumSubtotalAggregation: { type: 'number' },
                  unitSubtotalAggregation: { type: 'number' },
                  unitProductOptionPriceAggregation: { type: 'number' },
                  sumProductOptionPriceAggregation: { type: 'number' },
                  unitExpensePriceAggregation: { type: 'number' },
                  sumExpensePriceAggregation: { type: ['number', 'null'] },
                  unitDiscountAmountAggregation: { type: 'number' },
                  sumDiscountAmountAggregation: { type: 'number' },
                  unitPriceToPayAggregation: { type: 'number' },
                  sumPriceToPayAggregation: { type: 'number' },
                  orderReference: { type: ['string', 'null'] },
                  uuid: { type: 'string' },
                  isReturnable: { type: 'boolean' },
                },
                required: [
                  'merchantReference',
                  'name',
                  'sku',
                  'sumPrice',
                  'quantity',
                  'metadata',
                  'unitGrossPrice',
                  'sumGrossPrice',
                  'taxRate',
                  'unitNetPrice',
                  'sumNetPrice',
                  'unitPrice',
                  'unitTaxAmountFullAggregation',
                  'sumTaxAmountFullAggregation',
                  'refundableAmount',
                  'canceledAmount',
                  'sumSubtotalAggregation',
                  'unitSubtotalAggregation',
                  'unitPriceToPayAggregation',
                  'sumPriceToPayAggregation',
                  'orderReference',
                  'uuid',
                  'isReturnable',
                ],
              },
            },
            expenses: {
              type: 'array',
              items: {
                type: 'object',
                properties: {
                  type: { type: 'string' },
                  name: { type: 'string' },
                  sumPrice: { type: 'number' },
                  unitGrossPrice: { type: 'number' },
                  taxRate: { type: ['string', 'null'] },
                },
              },
            },
            billingAddress: {
              type: 'object',
              properties: {
                salutation: { type: 'string' },
                firstName: { type: 'string' },
                lastName: { type: 'string' },
                address1: { type: 'string' },
                city: { type: 'string' },
                zipCode: { type: 'string' },
              },
            },
            shippingAddress: {
              type: 'object',
              properties: {
                salutation: { type: 'string' },
                firstName: { type: 'string' },
                lastName: { type: 'string' },
                address1: { type: 'string' },
                city: { type: 'string' },
                zipCode: { type: 'string' },
              },
            },
            priceMode: { type: 'string' },
            payments: {
              type: 'array',
              items: {
                type: 'object',
                properties: {
                  amount: { type: 'number' },
                  paymentProvider: { type: 'string' },
                  paymentMethod: { type: 'string' },
                },
              },
            },
            shipments: {
              type: 'array',
              items: {
                type: 'object',
                properties: {
                  shipmentMethodName: { type: 'string' },
                  carrierName: { type: 'string' },
                },
              },
            },
          },
          required: [
            'merchantReferences',
            'createdAt',
            'totals',
            'currencyIsoCode',
            'items',
            'expenses',
            'billingAddress',
            'shippingAddress',
            'priceMode',
            'payments',
            'shipments',
          ],
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
            'order-shipments': {
              type: 'array',
              items: {
                type: 'object',
                properties: {
                  id: { type: 'string' },
                  type: { type: 'string' },
                },
              },
            },
            merchants: {
              type: 'array',
              items: {
                type: 'object',
                properties: {
                  id: { type: 'string' },
                  type: { type: 'string' },
                },
              },
            },
          },
        },
      },
      required: ['type', 'id', 'attributes', 'links'],
    },
    links: {
      type: 'object',
      properties: {
        self: { type: 'string' },
      },
    },
    included: {
      type: 'array',
      items: {
        type: 'object',
        properties: {
          type: { type: 'string' },
          id: { type: 'string' },
          attributes: {
            type: 'object',
            properties: {
              itemUuids: {
                type: 'array',
                items: { type: 'string' },
              },
            },
          },
        },
      },
    },
  },
  required: ['data'],
  additionalProperties: true, // Allows extra fields like "included" without validation failure
}

export default ordersSchema
