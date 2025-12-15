export const API_BASE = import.meta.env.VITE_API_BASE ?? 'http://127.0.0.1:8000/api'

export type HttpOptions = {
  method?: 'GET'|'POST'|'PUT'|'PATCH'|'DELETE'
  body?: unknown
  token?: string | null
}

export async function http<T=unknown>(path: string, opts: HttpOptions = {}): Promise<T> {
  const { method='GET', body, token } = opts

  const headers: Record<string,string> = {
    'Accept': 'application/json',
    ...(body ? { 'Content-Type': 'application/json' } : {}),
    ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
  }

  const res = await fetch(`${API_BASE}${path}`, {
    method,
    headers,
    body: body ? JSON.stringify(body) : undefined,
    // credentials: 'include', // abilita se ti serve inviare cookie
  })

  const contentType = res.headers.get('content-type') || ''
  const isJson = contentType.includes('application/json')
  const data = isJson ? await res.json().catch(() => null) : null

  if (!res.ok) {
    // messaggio più pulito
    const msg = (data && (data.error || data.message)) || `HTTP ${res.status}`

    if (res.status === 401) {
      // token non valido/scaduto → logout + redirect a /login
      try {
        const { setAuth } = await import('../store/auth.store')
        setAuth(null, null)
      } catch {}
      window.location.assign('/login')
    }

    throw new Error(msg)
  }

  return (data ?? ({} as any)) as T
}

