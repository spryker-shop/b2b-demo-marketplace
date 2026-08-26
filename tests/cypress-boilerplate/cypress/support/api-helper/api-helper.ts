import Ajv from 'ajv'

const ajv = new Ajv()

export const validateSchema = (schema, response) => {
  const validate = ajv.compile(schema)
  const valid = validate(response.body)
  if (!valid) {
    console.error('Schema validation errors test:', validate.errors)
    const errors = (validate.errors || [])
      .map((error) => {
        return `Path: ${error.instancePath}\nKeyword: ${error.keyword}\nMessage: ${error.message}\nParams: ${JSON.stringify(error.params, null, 2)}\nSchema Path: ${error.schemaPath}`
      })
      .join('\n\n')

    const formatResponse = (response) => {
      const { body } = response
      return JSON.stringify({ body }, null, 2)
    }

    const responseJson = formatResponse(response)
    throw new Error(
      `Schema validation failed:\n\n${errors}\n\nResponse:\n${responseJson}`
    )
  }
  return valid
}
