export type Role = 'Guest' | 'User' | 'Admin'

export function hasAnyRole(role: Role, allowed: Role[]) {
  return allowed.includes(role)
}
