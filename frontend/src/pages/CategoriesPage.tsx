import { useEffect, useMemo, useState } from 'react'
import { Container, Button, Table, Spinner, Alert, Row, Col, Form, InputGroup } from 'react-bootstrap'
import { getCategories } from '../services/categories.service'
import PageHeader from '../components/layout/PageHeader'
import DateCell from '../components/ui/DateCell'

type Category = {
  id: number
  name: string
  slug?: string
  created_at?: string
}

type SortBy = 'created_at' | 'name' | 'slug'
type SortDir = 'asc' | 'desc'

export default function CategoriesPage() {
  const [data, setData] = useState<Category[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  // mini toolbar
  const [q, setQ] = useState('')
  const [sortBy, setSortBy] = useState<SortBy>('created_at')
  const [sortDir, setSortDir] = useState<SortDir>('desc')

  async function load() {
    try {
      setLoading(true)
      setError(null)
      const res = await getCategories()
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
        const res = await getCategories()
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
    let out = data.filter(c =>
      !qnorm ||
      c.name.toLowerCase().includes(qnorm) ||
      (c.slug ?? '').toLowerCase().includes(qnorm)
    )

    out.sort((a, b) => {
      let cmp = 0
      if (sortBy === 'name') cmp = a.name.localeCompare(b.name)
      else if (sortBy === 'slug') cmp = (a.slug ?? '').localeCompare(b.slug ?? '')
      else {
        const da = a.created_at ? new Date(a.created_at).getTime() : 0
        const db = b.created_at ? new Date(b.created_at).getTime() : 0
        cmp = da - db
      }
      return sortDir === 'asc' ? cmp : -cmp
    })

    return out
  }, [data, q, sortBy, sortDir])

  const resetFilters = () => {
    setQ('')
    setSortBy('created_at')
    setSortDir('desc')
  }

  return (
    <Container className="py-4">
      <PageHeader
        title="Categories"
        subtitle="Gestisci le categorie"
        breadcrumbs={[{ label: 'Home', to: '/' }, { label: 'Categories' }]}
        actions={
          <div className="d-flex gap-2">
            <Button size="sm" variant="outline-secondary" onClick={load} disabled={loading}>
              {loading ? 'Aggiorno…' : 'Aggiorna'}
            </Button>
            <Button size="sm" onClick={() => alert('TODO: form categoria')}>
              + Aggiungi categoria
            </Button>
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
                placeholder="Nome o slug…"
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
                <option value="name">Nome</option>
                <option value="slug">Slug</option>
              </Form.Select>
              <Button
                variant="outline-secondary"
                onClick={() => setSortDir((d) => (d === 'asc' ? 'desc' : 'asc'))}
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
              <th className="text-nowrap">Slug</th>
              <th className="text-nowrap">Creato il</th>
            </tr>
          </thead>
          <tbody>
            {filtered.map(c => (
              <tr key={c.id} className="align-middle">
                <td className="text-muted">{c.id}</td>
                <td>{c.name}</td>
                <td>{c.slug ?? '-'}</td>
                <td><DateCell value={c.created_at} /></td>
              </tr>
            ))}
            {filtered.length === 0 && (
              <tr>
                <td colSpan={4} className="text-center text-muted py-4">Nessuna categoria</td>
              </tr>
            )}
          </tbody>
        </Table>
      )}
    </Container>
  )
}


