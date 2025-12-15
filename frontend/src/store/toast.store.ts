import { Subject } from 'rxjs'

export type ToastKind = 'success' | 'danger' | 'info' | 'warning'
export type ToastMsg = { id: number; title?: string; body: string; variant?: ToastKind; delay?: number }

const bus = new Subject<ToastMsg>()
export const toast$ = bus.asObservable()

let _id = 1
export function showToast(msg: Omit<ToastMsg, 'id'>) {
  bus.next({ id: _id++, delay: 3000, variant: 'success', ...msg })
}

export const toast = {
  success: (body: string, opts: Partial<ToastMsg> = {}) => showToast({ body, variant: 'success', ...opts }),
  error:   (body: string, opts: Partial<ToastMsg> = {}) => showToast({ body, variant: 'danger',  ...opts }),
  info:    (body: string, opts: Partial<ToastMsg> = {}) => showToast({ body, variant: 'info',    ...opts }),
  warn:    (body: string, opts: Partial<ToastMsg> = {}) => showToast({ body, variant: 'warning', ...opts }),
}
