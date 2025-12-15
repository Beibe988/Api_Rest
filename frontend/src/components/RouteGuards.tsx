import type { ReactNode } from 'react'
import { Navigate, useLocation } from 'react-router-dom'
import { useObservable } from '../lib/rx/useObservable'
import { token$, role$, hydrated$ } from '../store/auth.store'
import type { Role } from '../store/auth.store'

function CenteredSpinner() {
  return (
    <div className="d-flex justify-content-center py-5">
      <div className="spinner-border" />
    </div>
  )
}

export function RequireAuth({ children }: { children: ReactNode }) {
  const ready = useObservable(hydrated$, false)
  const token = useObservable(token$, null)
  const loc = useLocation()

  if (!ready) return <CenteredSpinner />
  if (!token) return <Navigate to="/login" state={{ from: loc }} replace />
  return <>{children}</>
}

export function RequireRoles({ roles, children }: { roles: Role[]; children: ReactNode }) {
  const ready = useObservable(hydrated$, false)
  const token = useObservable(token$, null)
  const role  = useObservable(role$, 'Guest' as Role)
  const loc = useLocation()

  if (!ready) return <CenteredSpinner />
  if (!token) return <Navigate to="/login" state={{ from: loc }} replace />
  if (!roles.includes(role)) return <Navigate to="/" replace />
  return <>{children}</>
}

export function GuestOnly({ children }: { children: ReactNode }) {
  const ready = useObservable(hydrated$, false)
  const token = useObservable(token$, null)

  if (!ready) return <CenteredSpinner />
  if (token) return <Navigate to="/" replace />
  return <>{children}</>
}


