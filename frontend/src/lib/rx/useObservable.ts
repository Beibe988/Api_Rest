import { useEffect, useState } from 'react'
import type { Observable } from 'rxjs'
export function useObservable<T>(obs$: Observable<T>, initial: T) {
  const [value, setValue] = useState<T>(initial)
  useEffect(()=>{ const sub = obs$.subscribe(setValue); return () => sub.unsubscribe() }, [obs$])
  return value
}
