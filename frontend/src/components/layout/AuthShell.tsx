import { Container } from 'react-bootstrap'
import type { ReactNode } from 'react'

export default function AuthShell({ children }: { children: ReactNode }) {
  return (
    <div className="min-vh-100 d-flex flex-column bg-black auth-bg">
      <Container className="flex-grow-1 d-flex align-items-center justify-content-center py-4">
        <div className="card card-neo w-100" style={{ maxWidth: 920 }}>
          <div className="card-body p-3 p-md-4 p-lg-5">{children}</div>
        </div>
      </Container>
    </div>
  )
}
