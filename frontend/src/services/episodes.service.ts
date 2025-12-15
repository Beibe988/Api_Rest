import { firstValueFrom } from 'rxjs'
import { token$ } from '../store/auth.store'
import { http } from '../lib/http'

/** Tipo usato nel FE (UI): usiamo episode_number per coerenza con la pagina */
export type Episode = {
  id: number
  title: string
  season?: number
  episode_number?: number
  serie_tv_id?: number
  created_at?: string
  updated_at?: string
}

/** DTO accettati dal BE: accettiamo episode_number ma traduciamo in episode se serve */
export type CreateEpisodeDTO = {
  title: string
  season?: number
  episode_number?: number
  serie_tv_id?: number
}
export type UpdateEpisodeDTO = Partial<CreateEpisodeDTO>

/** Helper: prende un oggetto dal BE e garantisce episode_number lato FE */
function normalizeEpisode(raw: any): Episode {
  if (!raw || typeof raw !== 'object') return raw as Episode
  return {
    ...raw,
    episode_number: raw.episode_number ?? raw.episode ?? raw.epNumber ?? undefined,
  }
}

/** Se il BE risponde {data:[...]} o direttamente [...] normalizziamo */
function normalizeArray<T>(res: any): T[] {
  return Array.isArray(res) ? res : (res?.data ?? [])
}

/** Lista episodi */
export async function getEpisodes(): Promise<Episode[]> {
  const token = await firstValueFrom(token$)
  const res = await http<any>('/episodes', { token })
  return normalizeArray<any>(res).map(normalizeEpisode)
}

/** Dettaglio episodio */
export async function getEpisode(id: number): Promise<Episode> {
  const token = await firstValueFrom(token$)
  const res = await http<any>(`/episodes/${id}`, { token })
  return normalizeEpisode(res)
}

/** Crea nuovo episodio (User/Admin) */
export async function createEpisode(payload: CreateEpisodeDTO): Promise<Episode> {
  const token = await firstValueFrom(token$)
  // Se il tuo BE si aspetta "episode" invece di "episode_number", effettuiamo mapping in uscita.
  const body = {
    ...payload,
    episode: (payload as any).episode ?? payload.episode_number ?? undefined,
  }
  const res = await http<any>('/episodes', { method: 'POST', body, token })
  return normalizeEpisode(res)
}

/** Aggiorna episodio (owner/Admin) */
export async function updateEpisode(id: number, payload: UpdateEpisodeDTO): Promise<Episode> {
  const token = await firstValueFrom(token$)
  const body = {
    ...payload,
    episode: (payload as any).episode ?? payload.episode_number ?? undefined,
  }
  const res = await http<any>(`/episodes/${id}`, { method: 'PUT', body, token })
  return normalizeEpisode(res)
}

/** Elimina episodio (owner/Admin) */
export async function deleteEpisode(id: number): Promise<{ message: string }> {
  const token = await firstValueFrom(token$)
  return http<{ message: string }>(`/episodes/${id}`, { method: 'DELETE', token })
}
