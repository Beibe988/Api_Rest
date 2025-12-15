import { useMemo, useState, useRef, useEffect } from 'react'
import { Row, Col, Form, Button, Alert, Spinner, InputGroup } from 'react-bootstrap'
import { useObservable } from '../lib/rx/useObservable'
import { form$, errors$, isValid$, setField, resetForm, getFormSnapshot } from '../store/registration.store'
import { RegistrationData } from '../types/registration'
import { register, type RegisterPayload } from '../services/auth.service'
import { useNavigate, Link } from 'react-router-dom'
import PageHeader from '../components/layout/PageHeader'
import AuthShell from '../components/layout/AuthShell'

export default function RegisterPage() {
  const form = useObservable(form$, getFormSnapshot())
  const errs = useObservable(errors$, {} as Record<string, string>)
  const ok   = useObservable(isValid$, false)

  const [submitting, setSubmitting] = useState(false)
  const [submitted, setSubmitted] = useState(false)
  const [serverError, setServerError] = useState<string | null>(null)
  const [serverOk, setServerOk] = useState<string | null>(null)

  const [showPwd, setShowPwd] = useState(false)
  const [showPwd2, setShowPwd2] = useState(false)
  const emailRef = useRef<HTMLInputElement | null>(null)

  const navigate = useNavigate()
  const dtoPreview = useMemo(() => new RegistrationData(form).toDTO(), [form])

  useEffect(() => { emailRef.current?.focus() }, [])

  function buildPayload(): RegisterPayload {
    return new RegistrationData(form).toDTO()
  }

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    setSubmitted(true)
    setServerError(null)
    setServerOk(null)

    if (!ok) {
      setServerError('Controlla i campi evidenziati.')
      return
    }
    const payload = buildPayload()

    try {
      setSubmitting(true)
      await register(payload)
      setServerOk('Registrazione avvenuta con successo. Reindirizzamento al login…')
      resetForm()
      setSubmitted(false)
      navigate('/login', {
        replace: true,
        state: {
          justRegistered: true,
          email: form.personal.email?.trim().toLowerCase() || ''
        }
      })
    } catch (err: any) {
      setServerError(err?.message ?? 'Errore durante la registrazione')
    } finally {
      setSubmitting(false)
    }
  }

  const hasErr = (key: string) => !!(errs as any)[key]
  const invalid = (key: string) => submitted && hasErr(key)
  const msg = (key: string) => (errs as any)[key] ?? ''

  // Helpers di normalizzazione
  const onCFChange = (v: string) => setField('personal.fiscalCode', v.toUpperCase())
  const onBirthProvChange = (v: string) => setField('personal.birthProvince', v.toUpperCase())
  const onResProvChange = (v: string) => setField('address.province', v.toUpperCase())

  return (
    <AuthShell>
      <PageHeader
        title="Crea un account"
        subtitle="Registrati per iniziare a gestire i tuoi contenuti"
        breadcrumbs={[{ label: 'Home', to: '/' }, { label: 'Registrazione' }]}
        divider={false}
        right={
          <div className="d-flex gap-2">
            <Button as={Link as any} to="/login" size="sm" variant="outline-secondary">
              Hai già un account? Login
            </Button>
          </div>
        }
      />

      {/* Divisore uniforme come le altre sezioni */}
      <hr className="section-hr" />

      {/* Etichetta sezione (verde/nero) */}
      <div className="section-label">Dati anagrafici</div>

      <Row className="justify-content-center">
        <Col md={13} lg={12} xl={11}>
          {serverError && <Alert variant="danger" role="alert">{serverError}</Alert>}
          {serverOk && <Alert variant="success" role="alert">{serverOk}</Alert>}

          <Form noValidate onSubmit={handleSubmit}>
            {/* Dati anagrafici (OBBLIGATORI) */}
            <Row className="g-3">
              <Col md={6}>
                <Form.Group controlId="name">
                  <Form.Label>Nome*</Form.Label>
                  <Form.Control
                    value={form.personal.name}
                    isInvalid={invalid('personal.name')}
                    onChange={e => setField('personal.name', e.target.value)}
                    placeholder="Mario"
                    required
                    autoComplete="given-name"
                  />
                  <Form.Control.Feedback type="invalid">{msg('personal.name')}</Form.Control.Feedback>
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group controlId="surname">
                  <Form.Label>Cognome*</Form.Label>
                  <Form.Control
                    value={form.personal.surname}
                    isInvalid={invalid('personal.surname')}
                    onChange={e => setField('personal.surname', e.target.value)}
                    placeholder="Rossi"
                    required
                    autoComplete="family-name"
                  />
                  <Form.Control.Feedback type="invalid">{msg('personal.surname')}</Form.Control.Feedback>
                </Form.Group>
              </Col>

              <Col md={6}>
                <Form.Group controlId="email">
                  <Form.Label>Email*</Form.Label>
                  <Form.Control
                    ref={emailRef}
                    type="email"
                    value={form.personal.email}
                    isInvalid={invalid('personal.email')}
                    onChange={e => setField('personal.email', e.target.value)}
                    placeholder="mario.rossi@example.com"
                    required
                    autoComplete="email"
                  />
                  <Form.Control.Feedback type="invalid">{msg('personal.email')}</Form.Control.Feedback>
                </Form.Group>
              </Col>
              <Col md={3}>
                <Form.Group controlId="birthDate">
                  <Form.Label>Data di nascita*</Form.Label>
                  <Form.Control
                    type="date"
                    value={form.personal.birthDate || ''}
                    isInvalid={invalid('personal.birthDate')}
                    onChange={e => setField('personal.birthDate', e.target.value)}
                    required
                    autoComplete="bday"
                  />
                  <Form.Control.Feedback type="invalid">{msg('personal.birthDate')}</Form.Control.Feedback>
                </Form.Group>
              </Col>
              <Col md={3}>
                <Form.Group controlId="gender">
                  <Form.Label>Sesso*</Form.Label>
                  <Form.Select
                    value={form.personal.gender || ''}
                    isInvalid={invalid('personal.gender')}
                    onChange={e => setField('personal.gender', e.target.value)}
                    required
                  >
                    <option value="" disabled>Seleziona…</option>
                    <option value="M">Uomo</option>
                    <option value="F">Donna</option>
                  </Form.Select>
                  <Form.Control.Feedback type="invalid">{msg('personal.gender')}</Form.Control.Feedback>
                </Form.Group>
              </Col>

              <Col md={6}>
                <Form.Group controlId="birthCity">
                  <Form.Label>Comune di nascita*</Form.Label>
                  <Form.Control
                    value={form.personal.birthCity}
                    isInvalid={invalid('personal.birthCity')}
                    onChange={e => setField('personal.birthCity', e.target.value)}
                    placeholder="Roma"
                    required
                  />
                  <Form.Control.Feedback type="invalid">{msg('personal.birthCity')}</Form.Control.Feedback>
                </Form.Group>
              </Col>
              <Col md={2}>
                <Form.Group controlId="birthProvince">
                  <Form.Label>Provincia*</Form.Label>
                  <Form.Control
                    value={form.personal.birthProvince}
                    isInvalid={invalid('personal.birthProvince')}
                    onChange={e => onBirthProvChange(e.target.value)}
                    placeholder="RM"
                    required
                    inputMode="text"
                  />
                  <Form.Control.Feedback type="invalid">{msg('personal.birthProvince')}</Form.Control.Feedback>
                </Form.Group>
              </Col>
              <Col md={4}>
                <Form.Group controlId="fiscalCode">
                  <Form.Label>Codice Fiscale*</Form.Label>
                  <Form.Control
                    value={form.personal.fiscalCode}
                    isInvalid={invalid('personal.fiscalCode')}
                    onChange={e => onCFChange(e.target.value)}
                    placeholder="RSSMRA80A01H501U"
                    required
                    autoCapitalize="characters"
                    autoComplete="off"
                    inputMode="text"
                  />
                  <Form.Control.Feedback type="invalid">{msg('personal.fiscalCode')}</Form.Control.Feedback>
                </Form.Group>
              </Col>

              <Col md={6}>
                <Form.Group controlId="displayName">
                  <Form.Label>Display name (opzionale)</Form.Label>
                  <Form.Control
                    value={form.personal.displayName || ''}
                    onChange={e => setField('personal.displayName', e.target.value)}
                    placeholder="Mario R."
                    autoComplete="nickname"
                  />
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group controlId="phone">
                  <Form.Label>Telefono</Form.Label>
                  <Form.Control
                    value={form.personal.phone || ''}
                    onChange={e => setField('personal.phone', e.target.value)}
                    placeholder="+39 333 1234567"
                    autoComplete="tel"
                  />
                </Form.Group>
              </Col>
            </Row>

            <hr className="my-4" />

            {/* Residenza (OPZIONALE: niente required, niente isInvalid) */}
            <div className="section-label">Residenza (opzionale)</div>
            <Row className="g-3">
              <Col md={8}>
                <Form.Group controlId="street">
                  <Form.Label>Indirizzo</Form.Label>
                  <Form.Control
                    value={form.address.street}
                    onChange={e => setField('address.street', e.target.value)}
                    placeholder="Via Roma 1"
                    autoComplete="address-line1"
                  />
                </Form.Group>
              </Col>
              <Col md={4}>
                <Form.Group controlId="city">
                  <Form.Label>Città</Form.Label>
                  <Form.Control
                    value={form.address.city}
                    onChange={e => setField('address.city', e.target.value)}
                    placeholder="Roma"
                    autoComplete="address-level2"
                  />
                </Form.Group>
              </Col>
              <Col md={4}>
                <Form.Group controlId="province">
                  <Form.Label>Provincia</Form.Label>
                  <Form.Control
                    value={form.address.province}
                    onChange={e => onResProvChange(e.target.value)}
                    placeholder="RM"
                    autoComplete="address-level1"
                  />
                </Form.Group>
              </Col>
              <Col md={4}>
                <Form.Group controlId="postalCode">
                  <Form.Label>CAP</Form.Label>
                  <Form.Control
                    value={form.address.postalCode}
                    onChange={e => setField('address.postalCode', e.target.value)}
                    placeholder="00100"
                    autoComplete="postal-code"
                    inputMode="numeric"
                  />
                </Form.Group>
              </Col>
              <Col md={4}>
                <Form.Group controlId="country">
                  <Form.Label>Paese</Form.Label>
                  <Form.Control
                    value={form.address.country}
                    onChange={e => setField('address.country', e.target.value)}
                    placeholder="Italia"
                    autoComplete="country-name"
                  />
                </Form.Group>
              </Col>
            </Row>

            <hr className="my-4" />

            {/* Credenziali (OBBLIGATORIE) */}
            <div className="section-label">Credenziali</div>
            <Row className="g-3">
              <Col md={6}>
                <Form.Group controlId="password">
                  <Form.Label>Password*</Form.Label>
                  <InputGroup>
                    <Form.Control
                      type={showPwd ? 'text' : 'password'}
                      value={form.credentials.password}
                      isInvalid={invalid('credentials.password')}
                      onChange={e => setField('credentials.password', e.target.value)}
                      placeholder="Min 8 caratteri"
                      required
                      autoComplete="new-password"
                      aria-describedby="passwordHelp"
                    />
                    <Button
                      variant="outline-secondary"
                      onClick={() => setShowPwd(s => !s)}
                      type="button"
                      aria-label={showPwd ? 'Nascondi password' : 'Mostra password'}
                    >
                      <i className={`bi ${showPwd ? 'bi-eye-slash' : 'bi-eye'} me-1`} />
                      {showPwd ? 'Nascondi' : 'Mostra'}
                    </Button>
                    <Form.Control.Feedback type="invalid">
                      {msg('credentials.password')}
                    </Form.Control.Feedback>
                  </InputGroup>
                  <Form.Text id="passwordHelp" muted>
                    Usa almeno 8 caratteri, meglio se con numeri e simboli.
                  </Form.Text>
                </Form.Group>
              </Col>
              <Col md={6}>
                <Form.Group controlId="confirmPassword">
                  <Form.Label>Conferma password*</Form.Label>
                  <InputGroup>
                    <Form.Control
                      type={showPwd2 ? 'text' : 'password'}
                      value={form.credentials.confirmPassword}
                      isInvalid={invalid('credentials.confirmPassword')}
                      onChange={e => setField('credentials.confirmPassword', e.target.value)}
                      required
                      autoComplete="new-password"
                    />
                    <Button
                      variant="outline-secondary"
                      onClick={() => setShowPwd2(s => !s)}
                      type="button"
                      aria-label={showPwd2 ? 'Nascondi password' : 'Mostra password'}
                    >
                      <i className={`bi ${showPwd2 ? 'bi-eye-slash' : 'bi-eye'} me-1`} />
                      {showPwd2 ? 'Nascondi' : 'Mostra'}
                    </Button>
                    <Form.Control.Feedback type="invalid">
                      {msg('credentials.confirmPassword')}
                    </Form.Control.Feedback>
                  </InputGroup>
                </Form.Group>
              </Col>
            </Row>

            <div className="d-flex justify-content-between align-items-center mt-4">
              <small className="text-muted">I campi contrassegnati con * sono obbligatori.</small>
              <div className="d-flex gap-2">
                <Button variant="secondary" type="button" onClick={resetForm} disabled={submitting}>
                  Reset
                </Button>
                <Button variant="primary" type="submit" disabled={submitting}>
                  {submitting ? (<><Spinner size="sm" className="me-2" /> Invio…</>) : 'Crea account'}
                </Button>
              </div>
            </div>
          </Form>

          {/* Debug DTO */}
          <div className="box-neo shadow-soft mt-3 p-3">
            <div className="text-muted mb-2">Anteprima DTO (debug)</div>
            <pre className="mb-0" style={{ whiteSpace: 'pre-wrap' }}>{JSON.stringify(dtoPreview, null, 2)}</pre>
          </div>
        </Col>
      </Row>
    </AuthShell>
  )
}









