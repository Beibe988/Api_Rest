import { firstValueFrom } from 'rxjs'
import { token$ } from '../store/auth.store'
import { http } from '../lib/http'

export type Category = { id: number; name: string; slug?: string; created_at?: string }

export async function getCategories(): Promise<Category[]> {
  const token = await firstValueFrom(token$)
  const res = await http<Category[]>('/categories', { token })
  return Array.isArray(res) ? res : (res as any)?.data ?? []
}


