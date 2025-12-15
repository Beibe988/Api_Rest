import { useState, useEffect, useRef } from 'react'
import { Button, Card, Form, Alert, Container, Row, Col, Spinner, InputGroup } from 'react-bootstrap'
import { useLocation, useNavigate } from 'react-router-dom'
import { login, testLoginHash } from '../services/auth.service'
import { toast } from '../store/toast.store'
import PageHeader from '../components/layout/PageHeader'

export default function LoginPage() {
  const [email, setEmail] = useState('test@example.com')
  const [password, setPassword] = useState('password')
  const [showPassword, setShowPassword] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)

  const emailRef = useRef<HTMLInputElement | null>(null)

  const navigate = useNavigate()
  const location = useLocation()
  const from = (location.state as any)?.from?.pathname || '/'
  const justRegistered = (location.state as any)?.justRegistered
  const registeredEmail = (location.state as any)?.email

  // Precompila email se arrivi dalla registrazione + focus
  useEffect(() => {
    if (justRegistered && registeredEmail) setEmail(registeredEmail)
    emailRef.current?.focus()
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault()
    setError(null)
    setLoading(true)
    try {
      await login(email.trim(), password)
      toast.success('Bentornato!')
      navigate(from, { replace: true })
    } catch (err: any) {
      setError(err?.message ?? 'Login failed')
    } finally {
      setLoading(false)
    }
  }

  async function onTest() {
    setError(null)
    setLoading(true)
    try {
      await testLoginHash()
      toast.success('Bentornato (test)!')
      navigate(from, { replace: true })
    } catch (err: any) {
      setError(err?.message ?? 'Test login failed')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Container className="py-4">
      <PageHeader
        title="Login"
        subtitle="Accedi per gestire i tuoi contenuti"
        breadcrumbs={[{ label: 'Home', to: '/' }, { label: 'Login' }]}
        divider
      />

      <Row className="justify-content-center">
        <Col md={6} lg={5}>
          <Card>
            <Card.Body>
              <Card.Title className="mb-3">Accedi</Card.Title>

              {justRegistered && (
                <Alert variant="success" className="mb-3">
                  Registrazione completata! Ora effettua il login.
                </Alert>
              )}

              {error && <Alert variant="danger">{error}</Alert>}

              <Form onSubmit={onSubmit}>
                <Form.Group className="mb-3" controlId="loginEmail">
                  <Form.Label>Email</Form.Label>
                  <Form.Control
                    ref={emailRef}
                    type="email"
                    value={email}
                    onChange={e => setEmail(e.target.value)}
                    autoComplete="username"
                    required
                    disabled={loading}
                  />
                </Form.Group>

                <Form.Group className="mb-3" controlId="loginPassword">
                  <Form.Label>Password</Form.Label>
                  <InputGroup>
                    <Form.Control
                      type={showPassword ? 'text' : 'password'}
                      value={password}
                      onChange={e => setPassword(e.target.value)}
                      autoComplete="current-password"
                      required
                      disabled={loading}
                    />
                    <Button
                      variant="outline-secondary"
                      onClick={() => setShowPassword(s => !s)}
                      type="button"
                      disabled={loading}
                      aria-label={showPassword ? 'Nascondi password' : 'Mostra password'}
                    >
                      {showPassword ? 'Nascondi' : 'Mostra'}
                    </Button>
                  </InputGroup>
                  <Form.Text className="text-muted">Minimo 6 caratteri.</Form.Text>
                </Form.Group>

                <div className="d-flex gap-2">
                  <Button type="submit" disabled={loading}>
                    {loading ? (<><Spinner size="sm" className="me-2" /> Login…</>) : 'Login'}
                  </Button>
                  <Button variant="secondary" onClick={onTest} disabled={loading} type="button">
                    {loading ? '...' : 'Test hash-login'}
                  </Button>
                </div>
              </Form>
            </Card.Body>
          </Card>
        </Col>
      </Row>
    </Container>
  )
}



