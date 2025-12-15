import { firstValueFrom } from 'rxjs'
import { token$ } from '../store/auth.store'
import { http } from '../lib/http'

export type Movie = {
  id: number
  title: string
  year?: number
  language_id?: number
  created_at?: string
}

export async function getMovies(): Promise<Movie[]> {
  const token = await firstValueFrom(token$)
  const res = await http<Movie[]>('/movies', { token })
  return Array.isArray(res) ? res : (res as any)?.data ?? []
}


