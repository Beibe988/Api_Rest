// src/components/layout/PageHeader.tsx
import type { ReactNode, ElementType } from 'react'
import { Row, Col } from 'react-bootstrap'
import { Link } from 'react-router-dom'

export type Crumb = { label: string; to?: string }

type PageHeaderProps = {
  title: string
  subtitle?: string
  /** Area azioni a destra (retro-compat) */
  right?: ReactNode
  /** Alias moderno di right (se entrambi presenti, vince actions) */
  actions?: ReactNode
  /** Breadcrumb opzionale */
  breadcrumbs?: Crumb[]
  /** h-level del titolo */
  as?: ElementType
  /** Dimensione titolo: cambia classi Bootstrap */
  size?: 'sm' | 'md' | 'lg'
  /** Classe extra wrapper */
  className?: string
  /** Riga divisoria sotto l’header */
  divider?: boolean
}

export default function PageHeader({
  title,
  subtitle,
  right,
  actions: actionsProp,
  breadcrumbs,
  as: TitleTag = 'h1',
  size = 'md',
  className = 'mb-3',
  divider = false,
}: PageHeaderProps) {
  const actions = actionsProp ?? right

  // mapping dimensioni => classi Bootstrap
  const titleClass =
    size === 'sm' ? 'h5 mb-0' :
    size === 'lg' ? 'h3 mb-0' :
    'h4 mb-0' // md default

  const subtitleClass =
    size === 'sm' ? 'text-muted small' :
    size === 'lg' ? 'text-muted' :
    'text-muted small'

  return (
    <div className={className}>
      {/* Breadcrumb (opzionale) */}
      {breadcrumbs?.length ? (
        <nav aria-label="breadcrumb" className="mb-2">
          <ol className="breadcrumb mb-0">
            {breadcrumbs.map((c, i) => {
              const isLast = i === breadcrumbs.length - 1
              return (
                <li
                  key={`${c.label}-${i}`}
                  className={`breadcrumb-item ${isLast ? 'active' : ''}`}
                  aria-current={isLast ? 'page' : undefined}
                >
                  {!isLast && c.to ? <Link to={c.to}>{c.label}</Link> : c.label}
                </li>
              )
            })}
          </ol>
        </nav>
      ) : null}

      <Row className="align-items-center g-2">
        <Col>
          <TitleTag className={titleClass}>{title}</TitleTag>
          {subtitle ? <div className={subtitleClass}>{subtitle}</div> : null}
        </Col>

        {actions ? <Col xs="auto">{actions}</Col> : null}
      </Row>

      {divider && <hr className="mt-3 mb-0" />}
    </div>
  )
}

