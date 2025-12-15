import { Navbar, Nav, Container, Button } from 'react-bootstrap'
import { Link, useLocation } from 'react-router-dom'
import { user$, role$ } from '../store/auth.store'
import { useObservable } from '../lib/rx/useObservable'
import { logout } from '../store/auth.store'

export default function AppNavbar() {
  const user = useObservable(user$, null)
  const role = useObservable(role$, 'Guest')
  const { pathname } = useLocation()

  const authed   = !!user
  const isAdmin  = role === 'Admin'
  const isMember = role === 'User' || role === 'Admin'

  const is = (path: string) => pathname === path
  const starts = (prefix: string) => pathname.startsWith(prefix)

  return (
    <Navbar expand="md" className="navbar-neo">
      <Container>
        <Navbar.Brand as={Link} to="/">MediaHub</Navbar.Brand>

        <Navbar.Toggle />
        <Navbar.Collapse>
          <Nav className="me-auto">
            <Nav.Link as={Link} to="/" active={is('/')} aria-current={is('/') ? 'page' : undefined}>
              Home
            </Nav.Link>

            {isMember && (
              <Nav.Link
                as={Link}
                to="/movies"
                active={starts('/movies')}
                aria-current={starts('/movies') ? 'page' : undefined}
              >
                Movies
              </Nav.Link>
            )}

            {isMember && (
              <Nav.Link
                as={Link}
                to="/series"
                active={starts('/series')}
                aria-current={starts('/series') ? 'page' : undefined}
              >
                Series
              </Nav.Link>
            )}

            {isAdmin && (
              <Nav.Link
                as={Link}
                to="/categories"
                active={starts('/categories')}
                aria-current={starts('/categories') ? 'page' : undefined}
              >
                Categories
              </Nav.Link>
            )}

            {isAdmin && (
              <Nav.Link
                as={Link}
                to="/users"
                active={starts('/users')}
                aria-current={starts('/users') ? 'page' : undefined}
              >
                Users
              </Nav.Link>
            )}
          </Nav>

          <div className="d-flex align-items-center gap-2">
            {authed ? (
              <>
                <span className="text-light small">
                  {user!.name} {user!.surname} · <strong>{user!.role}</strong>
                </span>
                <Button size="sm" variant="outline-light" onClick={logout}>
                  Logout
                </Button>
              </>
            ) : (
              <>
                <Nav.Link as={Link} to="/login" className="btn btn-sm btn-outline-light">
                  Login
                </Nav.Link>
                <Nav.Link as={Link} to="/register" className="btn btn-sm btn-primary text-white">
                  Registrati
                </Nav.Link>
              </>
            )}
          </div>
        </Navbar.Collapse>
      </Container>
    </Navbar>
  )
}
