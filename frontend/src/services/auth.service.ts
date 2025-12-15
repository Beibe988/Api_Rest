import { http } from '../lib/http'
import { setAuth, type User } from '../store/auth.store'
import { RegistrationData, type RegistrationForm, type RegistrationDTO } from '../types/registration'

export type LoginResponse = { token: string; user: User }
export type RegisterPayload = RegistrationDTO
export type RegisterResponse = { message: string }

export async function login(email: string, password: string) {
  const data = await http<LoginResponse>('/login', {
    method: 'POST',
    body: { email, password },
  })
  setAuth(data.token, data.user)
  return data
}

export async function testLoginHash() {
  const data = await http<LoginResponse>('/test-login-hash', { method: 'GET' })
  setAuth(data.token, data.user)
  return data
}

export async function register(payload: RegisterPayload) {
  return http<RegisterResponse>('/register', {
    method: 'POST',
    body: payload,
  })
}

export async function registerFromForm(form: RegistrationForm) {
  const dto = new RegistrationData(form).toDTO()
  return register(dto)
}

export function logoutLocal() {
  setAuth(null, null)
}

