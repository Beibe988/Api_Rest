import { firstValueFrom } from 'rxjs'
import { token$ } from '../store/auth.store'
import { http } from '../lib/http'

export type UserRow = {
  id: number
  name: string
  surname: string
  email: string
  role: 'Guest' | 'User' | 'Admin'
  created_at?: string
}

export async function getUsers(): Promise<UserRow[]> {
  const token = await firstValueFrom(token$)
  const res = await http<UserRow[]>('/users', { token })
  return Array.isArray(res) ? res : (res as any)?.data ?? []
}


