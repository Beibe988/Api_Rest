import { useEffect, useMemo, useState } from 'react'
import { Container, Button, Table, Spinner, Alert, Row, Col, Form, InputGroup } from 'react-bootstrap'
import { useObservable } from '../lib/rx/useObservable'
import { role$ } from '../store/auth.store'
import { getSeries } from '../services/series.service'
import PageHeader from '../components/layout/PageHeader'
import DateCell from '../components/ui/DateCell'

type Series = {
  id: number
  title: string
  seasons?: number
  created_at?: string
}

type SortBy = 'created_at' | 'title' | 'seasons'
type SortDir = 'asc' | 'desc'

export default function SeriesPage() {
  const role = useObservable(role$, 'Guest')
  const canCreate = role === 'User' || role === 'Admin'

  const [data, setData] = useState<Series[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  // mini toolbar
  const [q, setQ] = useState('')
  const [sortBy, setSortBy] = useState<SortBy>('created_at')
  const [sortDir, setSortDir] = useState<SortDir>('desc')
  const [seasonMin, setSeasonMin] = useState<string>('') // string per input vuoto
  const [seasonMax, setSeasonMax] = useState<string>('')

  async function load() {
    try {
      setLoading(true)
      setError(null)
      const res = await getSeries()
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
        const res = await getSeries()
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
    const lo = seasonMin ? parseInt(seasonMin, 10) : undefined
    const hi = seasonMax ? parseInt(seasonMax, 10) : undefined

    let out = data.filter(s => {
      const matchQ = !qnorm || s.title.toLowerCase().includes(qnorm)
      const sv = s.seasons ?? 0
      const matchLo = lo === undefined || sv >= lo
      const matchHi = hi === undefined || sv <= hi
      return matchQ && matchLo && matchHi
    })

    out.sort((a, b) => {
      let cmp = 0
      if (sortBy === 'title') cmp = a.title.localeCompare(b.title)
      else if (sortBy === 'seasons') cmp = (a.seasons ?? 0) - (b.seasons ?? 0)
      else {
        const da = a.created_at ? new Date(a.created_at).getTime() : 0
        const db = b.created_at ? new Date(b.created_at).getTime() : 0
        cmp = da - db
      }
      return sortDir === 'asc' ? cmp : -cmp
    })

    return out
  }, [data, q, sortBy, sortDir, seasonMin, seasonMax])

  const resetFilters = () => {
    setQ('')
    setSortBy('created_at')
    setSortDir('desc')
    setSeasonMin('')
    setSeasonMax('')
  }

  return (
    <Container className="py-4">
      <PageHeader
        title="Series"
        subtitle="Gestisci le serie TV"
        breadcrumbs={[{ label: 'Home', to: '/' }, { label: 'Series' }]}
        actions={
          <div className="d-flex gap-2">
            <Button size="sm" variant="outline-secondary" onClick={load} disabled={loading}>
              {loading ? 'Aggiorno…' : 'Aggiorna'}
            </Button>
            {canCreate && (
              <Button size="sm" onClick={() => alert('TODO: form creazione serie')}>
                + Aggiungi serie
              </Button>
            )}
          </div>
        }
        divider
      />

      {/* MINI TOOLBAR */}
      <div className="mb-3">
        <Row className="g-2 align-items-end">
          <Col md={6}>
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
                <option value="seasons">Stagioni</option>
              </Form.Select>
              <Button
                variant="outline-secondary"
                onClick={() => setSortDir((d) => (d === 'asc' ? 'desc' : 'asc'))}
              >
                {sortDir === 'asc' ? '↑' : '↓'}
              </Button>
            </InputGroup>
          </Col>

          <Col md={1}>
            <Form.Label className="small text-muted">Min</Form.Label>
            <Form.Control
              type="number"
              inputMode="numeric"
              placeholder="0"
              value={seasonMin}
              onChange={(e) => setSeasonMin(e.target.value)}
            />
          </Col>
          <Col md={2}>
            <Form.Label className="small text-muted">Max</Form.Label>
            <Form.Control
              type="number"
              inputMode="numeric"
              placeholder="99"
              value={seasonMax}
              onChange={(e) => setSeasonMax(e.target.value)}
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
              <th className="text-nowrap">Stagioni</th>
              <th className="text-nowrap">Creato il</th>
            </tr>
          </thead>
          <tbody>
            {filtered.map(s => (
              <tr key={s.id} className="align-middle">
                <td className="text-muted">{s.id}</td>
                <td>{s.title}</td>
                <td>{s.seasons ?? '-'}</td>
                <td><DateCell value={s.created_at} /></td>
              </tr>
            ))}
            {filtered.length === 0 && (
              <tr>
                <td colSpan={4} className="text-center text-muted py-4">Nessuna serie</td>
              </tr>
            )}
          </tbody>
        </Table>
      )}
    </Container>
  )
}


