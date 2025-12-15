import { Nav } from 'react-bootstrap'
import { Link, useLocation } from 'react-router-dom'
import { useObservable } from '../../lib/rx/useObservable'
import { role$ } from '../../store/auth.store'

export default function LeftSidebar() {
  const location = useLocation()
  const role = useObservable(role$, 'Guest')

  const isAdmin  = role === 'Admin'
  const isMember = role === 'User' || role === 'Admin'

  const linkClass = (active: boolean) =>
    `px-3 py-2 rounded-2 sidebar-link ${active ? 'active' : ''}`

  return (
    <nav className="p-3 text-white-50 sidebar-wrap">
      <div className="fw-semibold text-uppercase small text-white-50 mb-2">Navigation</div>
      <Nav className="flex-column gap-1">
        <Nav.Link as={Link} to="/" className={linkClass(location.pathname === '/')}>Home</Nav.Link>
        {isMember && (
          <Nav.Link as={Link} to="/movies" className={linkClass(location.pathname.startsWith('/movies'))}>
            Movies
          </Nav.Link>
        )}
        {isMember && (
          <Nav.Link as={Link} to="/series" className={linkClass(location.pathname.startsWith('/series'))}>
            Series
          </Nav.Link>
        )}
        {isAdmin && (
          <Nav.Link as={Link} to="/categories" className={linkClass(location.pathname.startsWith('/categories'))}>
            Categories
          </Nav.Link>
        )}
        {isAdmin && (
          <Nav.Link as={Link} to="/users" className={linkClass(location.pathname.startsWith('/users'))}>
            Users
          </Nav.Link>
        )}
      </Nav>
    </nav>
  )
}

