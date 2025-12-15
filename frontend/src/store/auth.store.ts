import { BehaviorSubject, map, distinctUntilChanged } from 'rxjs'

export type Role = 'Guest' | 'User' | 'Admin'
export type User = { id: number; name: string; surname: string; role: Role }
export type AuthState = { token: string | null; user: User | null }

// --- helper: verifica scadenza del JWT lato client ---
function isExpired(token: string): boolean {
  try {
    const parts = token.split('.')
    if (parts.length < 2) return true
    const base64url = parts[1]
    const base64 = base64url.replace(/-/g, '+').replace(/_/g, '/')
    const jsonStr = decodeURIComponent(
      atob(base64)
        .split('')
        .map(c => '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2))
        .join('')
    )
    const payload = JSON.parse(jsonStr) as { exp?: number }
    if (!payload.exp) return false
    const now = Math.floor(Date.now() / 1000)
    return now >= payload.exp
  } catch {
    return true
  }
}

const initial: AuthState = (() => {
  try {
    const token = localStorage.getItem('token')
    const userJson = localStorage.getItem('user')
    const user = userJson ? (JSON.parse(userJson) as User) : null

    if (token && isExpired(token)) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      return { token: null, user: null }
    }
    return { token, user }
  } catch {
    return { token: null, user: null }
  }
})()

const auth$ = new BehaviorSubject<AuthState>(initial)
export const authState$ = auth$.asObservable()

export const token$ = authState$.pipe(map(s => s.token), distinctUntilChanged())
export const user$  = authState$.pipe(map(s => s.user),  distinctUntilChanged())
export const role$  = user$.pipe(
  map(u => (u?.role ?? 'Guest') as Role),
  distinctUntilChanged()
)

// Flag: store idratato da localStorage
export const hydrated$ = new BehaviorSubject<boolean>(false)
;(function hydrate() {
  hydrated$.next(true)
})()

// Comodo se ti serve sapere se hai un token
export const isAuthenticated$ = token$.pipe(
  map(t => !!t),
  distinctUntilChanged()
)

export function setAuth(token: string | null, user: User | null) {
  auth$.next({ token, user })
  if (token) localStorage.setItem('token', token); else localStorage.removeItem('token')
  if (user)  localStorage.setItem('user', JSON.stringify(user)); else localStorage.removeItem('user')
}

export function logout() {
  setAuth(null, null)
}
