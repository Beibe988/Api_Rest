import { useEffect, useMemo, useState } from 'react'
import { Container, Button, Table, Spinner, Alert, Row, Col, Form, InputGroup } from 'react-bootstrap'
import { useObservable } from '../lib/rx/useObservable'
import { role$ } from '../store/auth.store'
import PageHeader from '../components/layout/PageHeader'
import DateCell from '../components/ui/DateCell'
import { getEpisodes } from '../services/episodes.service'

type Episode = {
  id: number
  title: string
  season?: number
  episode?: number
  serie_tv_id?: number
  created_at?: string
}

type SortBy = 'created_at' | 'title' | 'season' | 'episode'
type SortDir = 'asc' | 'desc'

export default function EpisodesPage() {
  const role = useObservable(role$, 'Guest')
  const canCreate = role === 'User' || role === 'Admin'

  const [data, setData] = useState<Episode[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  // mini toolbar
  const [q, setQ] = useState('')
  const [sortBy, setSortBy] = useState<SortBy>('created_at')
  const [sortDir, setSortDir] = useState<SortDir>('desc')
  const [seasonMin, setSeasonMin] = useState<string>('')
  const [seasonMax, setSeasonMax] = useState<string>('')
  const [epMin, setEpMin] = useState<string>('')
  const [epMax, setEpMax] = useState<string>('')

  async function load() {
    try {
      setLoading(true)
      setError(null)
      const res = await getEpisodes()
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
        const res = await getEpisodes()
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
    const sLo = seasonMin ? parseInt(seasonMin, 10) : undefined
    const sHi = seasonMax ? parseInt(seasonMax, 10) : undefined
    const eLo = epMin ? parseInt(epMin, 10) : undefined
    const eHi = epMax ? parseInt(epMax, 10) : undefined

    let out = data.filter(ep => {
      const matchQ = !qnorm || ep.title.toLowerCase().includes(qnorm)
      const sv = ep.season ?? 0
      const ev = ep.episode ?? 0
      const matchSLo = sLo === undefined || sv >= sLo
      const matchSHi = sHi === undefined || sv <= sHi
      const matchELo = eLo === undefined || ev >= eLo
      const matchEHi = eHi === undefined || ev <= eHi
      return matchQ && matchSLo && matchSHi && matchELo && matchEHi
    })

    out.sort((a, b) => {
      let cmp = 0
      if (sortBy === 'title') cmp = a.title.localeCompare(b.title)
      else if (sortBy === 'season') cmp = (a.season ?? 0) - (b.season ?? 0)
      else if (sortBy === 'episode') cmp = (a.episode ?? 0) - (b.episode ?? 0)
      else {
        const da = a.created_at ? new Date(a.created_at).getTime() : 0
        const db = b.created_at ? new Date(b.created_at).getTime() : 0
        cmp = da - db
      }
      return sortDir === 'asc' ? cmp : -cmp
    })

    return out
  }, [data, q, sortBy, sortDir, seasonMin, seasonMax, epMin, epMax])

  const resetFilters = () => {
    setQ('')
    setSortBy('created_at')
    setSortDir('desc')
    setSeasonMin('')
    setSeasonMax('')
    setEpMin('')
    setEpMax('')
  }

  return (
    <Container className="py-4">
      <PageHeader
        title="Episodes"
        subtitle="Gestisci gli episodi delle serie"
        breadcrumbs={[{ label: 'Home', to: '/' }, { label: 'Episodes' }]}
        actions={
          <div className="d-flex gap-2">
            <Button size="sm" variant="outline-secondary" onClick={load} disabled={loading}>
              {loading ? 'Aggiorno…' : 'Aggiorna'}
            </Button>
            {canCreate && (
              <Button size="sm" onClick={() => alert('TODO: form creazione episodio')}>
                + Aggiungi episodio
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
                placeholder="Titolo episodio…"
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
                <option value="season">Stagione</option>
                <option value="episode">Episodio</option>
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
            <Form.Label className="small text-muted">Stagione da</Form.Label>
            <Form.Control
              type="number"
              inputMode="numeric"
              placeholder="min"
              value={seasonMin}
              onChange={(e) => setSeasonMin(e.target.value)}
            />
          </Col>
          <Col md={2}>
            <Form.Label className="small text-muted">Stagione a</Form.Label>
            <Form.Control
              type="number"
              inputMode="numeric"
              placeholder="max"
              value={seasonMax}
              onChange={(e) => setSeasonMax(e.target.value)}
            />
          </Col>

          <Col md={2}>
            <Form.Label className="small text-muted">Episodio da</Form.Label>
            <Form.Control
              type="number"
              inputMode="numeric"
              placeholder="min"
              value={epMin}
              onChange={(e) => setEpMin(e.target.value)}
            />
          </Col>
          <Col md={2}>
            <Form.Label className="small text-muted">Episodio a</Form.Label>
            <Form.Control
              type="number"
              inputMode="numeric"
              placeholder="max"
              value={epMax}
              onChange={(e) => setEpMax(e.target.value)}
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
              <th className="text-nowrap">Stagione</th>
              <th className="text-nowrap">Episodio</th>
              <th className="text-nowrap">Creato il</th>
            </tr>
          </thead>
          <tbody>
            {filtered.map(ep => (
              <tr key={ep.id} className="align-middle">
                <td className="text-muted">{ep.id}</td>
                <td>{ep.title}</td>
                <td>{ep.season ?? '-'}</td>
                <td>{ep.episode ?? '-'}</td>
                <td><DateCell value={ep.created_at} /></td>
              </tr>
            ))}
            {filtered.length === 0 && (
              <tr>
                <td colSpan={5} className="text-center text-muted py-4">Nessun episodio</td>
              </tr>
            )}
          </tbody>
        </Table>
      )}
    </Container>
  )
}