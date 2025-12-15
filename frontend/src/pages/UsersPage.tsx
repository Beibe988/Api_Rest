import { useEffect, useMemo, useState } from 'react'
import { Container, Table, Spinner, Alert, Row, Col, Form, InputGroup, Button } from 'react-bootstrap'
import PageHeader from '../components/layout/PageHeader'
import DateCell from '../components/ui/DateCell'
import { getUsers } from '../services/users.service'

type Role = 'Guest' | 'User' | 'Admin'
type UserRow = {
  id: number
  name: string
  surname: string
  email: string
  role: Role
  created_at?: string
}

type SortBy = 'created_at' | 'name' | 'surname' | 'email' | 'role'
type SortDir = 'asc' | 'desc'

function csvEscape(v: unknown): string {
  const s = String(v ?? '')
  if (/[",\n]/.test(s)) return `"${s.replace(/"/g, '""')}"`
  return s
}

export default function UsersPage() {
  const [data, setData] = useState<UserRow[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  // mini toolbar
  const [q, setQ] = useState('')
  const [role, setRole] = useState<Role | 'ALL'>('ALL')
  const [sortBy, setSortBy] = useState<SortBy>('created_at')
  const [sortDir, setSortDir] = useState<SortDir>('desc')

  async function load() {
    try {
      setLoading(true)
      setError(null)
      const res = await getUsers()
      setData(res)
    } catch (e: any) {
      setError(e?.message ?? 'Errore nel caricamento')
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    let alive = true
    ;(async () => {
      try {
        setLoading(true)
        setError(null)
        const res = await getUsers()
        if (!alive) return
        setData(res)
      } catch (e: any) {
        if (!alive) return
        setError(e?.message ?? 'Errore nel caricamento')
      } finally {
        if (alive) setLoading(false)
      }
    })()
    return () => { alive = false }
  }, [])

  const filtered = useMemo(() => {
    const qnorm = q.trim().toLowerCase()

    let out = data.filter(u => {
      const matchQ =
        !qnorm ||
        u.name.toLowerCase().includes(qnorm) ||
        u.surname.toLowerCase().includes(qnorm) ||
        u.email.toLowerCase().includes(qnorm)
      const matchRole = role === 'ALL' || u.role === role
      return matchQ && matchRole
    })

    out.sort((a, b) => {
      let cmp = 0
      if (sortBy === 'name') cmp = a.name.localeCompare(b.name)
      else if (sortBy === 'surname') cmp = a.surname.localeCompare(b.surname)
      else if (sortBy === 'email') cmp = a.email.localeCompare(b.email)
      else if (sortBy === 'role') cmp = a.role.localeCompare(b.role)
      else {
        const da = a.created_at ? new Date(a.created_at).getTime() : 0
        const db = b.created_at ? new Date(b.created_at).getTime() : 0
        cmp = da - db
      }
      return sortDir === 'asc' ? cmp : -cmp
    })

    return out
  }, [data, q, role, sortBy, sortDir])

  const resetFilters = () => {
    setQ('')
    setRole('ALL')
    setSortBy('created_at')
    setSortDir('desc')
  }

  function exportCsv() {
    const headers = ['id', 'name', 'surname', 'email', 'role', 'created_at']
    const rows = filtered.map(u => [
      u.id, u.name, u.surname, u.email, u.role, u.created_at ?? ''
    ])
    const csv = [headers.join(','), ...rows.map(r => r.map(csvEscape).join(','))].join('\n')
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `users_${new Date().toISOString().slice(0,19).replace(/[:T]/g,'-')}.csv`
    a.click()
    URL.revokeObjectURL(url)
  }

  return (
    <Container className="py-4">
      <PageHeader
        title="Users"
        subtitle="Gestisci gli utenti (solo Admin)"
        breadcrumbs={[{ label: 'Home', to: '/' }, { label: 'Users' }]}
        actions={
          <div className="d-flex gap-2">
            <Button size="sm" variant="outline-secondary" onClick={load} disabled={loading}>
              {loading ? 'Aggiorno…' : 'Aggiorna'}
            </Button>
            <Button size="sm" variant="outline-secondary" onClick={exportCsv} disabled={loading || filtered.length === 0}>
              Esporta CSV
            </Button>
          </div>
        }
        divider
      />

      {/* MINI TOOLBAR */}
      <div className="mb-3">
        <Row className="g-2 align-items-end">
          <Col md={5}>
            <Form.Label className="small text-muted">Cerca</Form.Label>
            <InputGroup>
              <Form.Control
                placeholder="Nome, cognome o email…"
                value={q}
                onChange={(e) => setQ(e.target.value)}
              />
              {q && (
                <Button variant="outline-secondary" onClick={() => setQ('')}>
                  Cancella
                </Button>
              )}
            </InputGroup>
          </Col>

          <Col md={3}>
            <Form.Label className="small text-muted">Ruolo</Form.Label>
            <Form.Select value={role} onChange={(e) => setRole(e.target.value as Role | 'ALL')}>
              <option value="ALL">Tutti</option>
              <option value="Guest">Guest</option>
              <option value="User">User</option>
              <option value="Admin">Admin</option>
            </Form.Select>
          </Col>

          <Col md={4}>
            <Form.Label className="small text-muted">Ordinamento</Form.Label>
            <InputGroup>
              <Form.Select value={sortBy} onChange={(e) => setSortBy(e.target.value as SortBy)}>
                <option value="created_at">Data creazione</option>
                <option value="name">Nome</option>
                <option value="surname">Cognome</option>
                <option value="email">Email</option>
                <option value="role">Ruolo</option>
              </Form.Select>
              <Button
                variant="outline-secondary"
                onClick={() => setSortDir(d => d === 'asc' ? 'desc' : 'asc')}
              >
                {sortDir === 'asc' ? '↑' : '↓'}
              </Button>
            </InputGroup>
          </Col>
        </Row>

        <div className="mt-2 d-flex gap-2">
          <Button size="sm" variant="outline-secondary" onClick={resetFilters}>
            Reset filtri
          </Button>
        </div>
      </div>

      {loading && <Spinner animation="border" />}
      {error && <Alert variant="danger">{error}</Alert>}

      {!loading && !error && (
        <Table className="data-table" striped hover responsive size="sm">
          <thead>
            <tr>
              <th className="text-nowrap">#</th>
              <th className="text-nowrap">Nome</th>
              <th className="text-nowrap">Cognome</th>
              <th className="text-nowrap">Email</th>
              <th className="text-nowrap">Ruolo</th>
              <th className="text-nowrap">Creato il</th>
            </tr>
          </thead>
          <tbody>
            {filtered.map(u => (
              <tr key={u.id} className="align-middle">
                <td className="text-muted">{u.id}</td>
                <td>{u.name}</td>
                <td>{u.surname}</td>
                <td>{u.email}</td>
                <td>{u.role}</td>
                <td><DateCell value={u.created_at} /></td>
              </tr>
            ))}
            {filtered.length === 0 && (
              <tr>
                <td colSpan={6} className="text-center text-muted py-4">Nessun utente</td>
              </tr>
            )}
          </tbody>
        </Table>
      )}
    </Container>
  )
}


