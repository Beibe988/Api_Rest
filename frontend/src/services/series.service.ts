import { firstValueFrom } from 'rxjs'
import { token$ } from '../store/auth.store'
import { http } from '../lib/http'

export type Series = { id: number; title: string; seasons?: number; created_at?: string }

export async function getSeries(): Promise<Series[]> {
  const token = await firstValueFrom(token$)
  const res = await http<Series[]>('/series', { token })
  return Array.isArray(res) ? res : (res as any)?.data ?? []
}


