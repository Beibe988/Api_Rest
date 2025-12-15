import { useEffect, useMemo, useState } from 'react'
import { Container, Button, Table, Spinner, Alert, Row, Col, Form, InputGroup } from 'react-bootstrap'
import { useObservable } from '../lib/rx/useObservable'
import { role$ } from '../store/auth.store'
import { getMovies } from '../services/movies.service'
import PageHeader from '../components/layout/PageHeader'
import DateCell from '../components/ui/DateCell'

type Movie = {
  id: number
  title: string
  year?: number
  language_id?: number
  created_at?: string
}

type SortBy = 'created_at' | 'title' | 'year'
type SortDir = 'asc' | 'desc'

export default function MoviesPage() {
  const role = useObservable(role$, 'Guest')
  const canCreate = role === 'User' || role === 'Admin'

  const [data, setData] = useState<Movie[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  // mini toolbar state
  const [q, setQ] = useState('')
  const [sortBy, setSortBy] = useState<SortBy>('created_at')
  const [sortDir, setSortDir] = useState<SortDir>('desc')
  const [yearMin, setYearMin] = useState<string>('') // string per gestire input vuoto
  const [yearMax, setYearMax] = useState<string>('')

  async function load() {
    try {
      setLoading(true)
      setError(null)
      const res = await getMovies()
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
        const res = await getMovies()
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
    const lo = yearMin ? parseInt(yearMin, 10) : undefined
    const hi = yearMax ? parseInt(yearMax, 10) : undefined

    let out = data.filter(m => {
      const matchQ = !qnorm || m.title.toLowerCase().includes(qnorm)
      const matchLo = lo === undefined || (m.year ?? -Infinity) >= lo
      const matchHi = hi === undefined || (m.year ?? Infinity) <= hi
      return matchQ && matchLo && matchHi
    })

    out.sort((a, b) => {
      let cmp = 0
      if (sortBy === 'title') {
        cmp = a.title.localeCompare(b.title)
      } else if (sortBy === 'year') {
        cmp = (a.year ?? 0) - (b.year ?? 0)
      } else {
        // created_at
        const da = a.created_at ? new Date(a.created_at).getTime() : 0
        const db = b.created_at ? new Date(b.created_at).getTime() : 0
        cmp = da - db
      }
      return sortDir === 'asc' ? cmp : -cmp
    })

    return out
  }, [data, q, sortBy, sortDir, yearMin, yearMax])

  const resetFilters = () => {
    setQ('')
    setSortBy('created_at')
    setSortDir('desc')
    setYearMin('')
    setYearMax('')
  }

  return (
    <Container className="py-4">
      <PageHeader
        title="Movies"
        subtitle="Gestisci il catalogo dei film"
        breadcrumbs={[{ label: 'Home', to: '/' }, { label: 'Movies' }]}
        actions={
          <div className="d-flex gap-2">
            <Button size="sm" variant="outline-secondary" onClick={load} disabled={loading}>
              {loading ? 'Aggiorno…' : 'Aggiorna'}
            </Button>
            {canCreate && (
              <Button size="sm" onClick={() => alert('TODO: form creazione film')}>
                + Aggiungi film
              </Button>
            )}
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
                placeholder="Titolo…"
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
            <Form.Label className="small text-muted">Ordinamento</Form.Label>
            <InputGroup>
              <Form.Select value={sortBy} onChange={(e) => setSortBy(e.target.value as SortBy)}>
                <option value="created_at">Data creazione</option>
                <option value="title">Titolo</option>
                <option value="year">Anno</option>
              </Form.Select>
              <Button
                variant="outline-secondary"
                onClick={() => setSortDir((d) => (d === 'asc' ? 'desc' : 'asc'))}
                title={sortDir === 'asc' ? 'Ordina decrescente' : 'Ordina crescente'}
              >
                {sortDir === 'asc' ? '↑' : '↓'}
              </Button>
            </InputGroup>
          </Col>

          <Col md={2}>
            <Form.Label className="small text-muted">Anno da</Form.Label>
            <Form.Control
              type="number"
              inputMode="numeric"
              placeholder="min"
              value={yearMin}
              onChange={(e) => setYearMin(e.target.value)}
            />
          </Col>

          <Col md={2}>
            <Form.Label className="small text-muted">Anno a</Form.Label>
            <Form.Control
              type="number"
              inputMode="numeric"
              placeholder="max"
              value={yearMax}
              onChange={(e) => setYearMax(e.target.value)}
            />
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
              <th className="text-nowrap">Titolo</th>
              <th className="text-nowrap">Anno</th>
              <th className="text-nowrap">Creato il</th>
            </tr>
          </thead>
          <tbody>
            {filtered.map(m => (
              <tr key={m.id} className="align-middle">
                <td className="text-muted">{m.id}</td>
                <td>{m.title}</td>
                <td>{m.year ?? '-'}</td>
                <td><DateCell value={m.created_at} /></td>
              </tr>
            ))}
            {filtered.length === 0 && (
              <tr>
                <td colSpan={4} className="text-center text-muted py-4">
                  Nessun film
                </td>
              </tr>
            )}
          </tbody>
        </Table>
      )}
    </Container>
  )
}


