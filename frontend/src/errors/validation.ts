export class FieldError extends Error {
  constructor(public field: string, message: string) {
    super(message)
    this.name = 'FieldError'
  }
}

export type ErrorMap = Record<string, string>

export class FormValidationError extends Error {
  constructor(public errors: ErrorMap) {
    super('Validation failed')
    this.name = 'FormValidationError'
  }
}
