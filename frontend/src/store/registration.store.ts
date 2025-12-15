import { BehaviorSubject, map, distinctUntilChanged } from 'rxjs'
import type { RegistrationForm, Gender } from '../types/registration'
import { validateRegistration } from '../validation/registration.validators'

const initial: RegistrationForm = {
  personal: {
    name: '',
    surname: '',
    email: '',
    displayName: '',
    birthDate: '',           
    gender: 'M' as Gender,   
    birthCity: '',
    birthProvince: '',
    fiscalCode: '',
    phone: '',
  },
  address: {
    street: '',
    city: '',
    province: '',
    postalCode: '',
    country: 'Italia',
  },
  credentials: {
    password: '',
    confirmPassword: '',
  },
}

const formSubject = new BehaviorSubject<RegistrationForm>(initial)
export const form$   = formSubject.asObservable()
export const errors$ = form$.pipe(
  map(f => validateRegistration(f)),
  distinctUntilChanged((a, b) => JSON.stringify(a) === JSON.stringify(b))
)
export const isValid$ = errors$.pipe(
  map(errs => Object.keys(errs).length === 0),
  distinctUntilChanged()
)

export function setField(path: string, value: string) {
  const next = structuredClone(formSubject.value) as RegistrationForm
  const [root, key] = path.split('.') as ['personal'|'address'|'credentials', string]
  // @ts-expect-error indicizzazione dinamica
  next[root][key] = value
  formSubject.next(next)
}

export function resetForm() {
  formSubject.next(initial)
}

export function getFormSnapshot(): RegistrationForm {
  return formSubject.value
}
