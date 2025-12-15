import type { ReactNode } from 'react'
import { Container, Row, Col } from 'react-bootstrap'
import AppNavbar from '../AppNavbar'
import LeftSidebar from './LeftSidebar'

export default function AppShell({ children }: { children: ReactNode }) {
  return (
    <div className="app-shell min-vh-100 d-flex flex-column text-light">
      <AppNavbar />
      <Container fluid className="flex-grow-1">
        <Row className="g-0">
          {/* Sidebar: solo da lg+ */}
          <Col lg={2} className="d-none d-lg-block border-end border-green-700 sidebar-col">
            <LeftSidebar />
          </Col>

          {/* Content */}
          <Col xs={12} lg={10} className="p-3 p-lg-4">
            {children}
          </Col>
        </Row>
      </Container>
      {/* Footer opzionale
      <footer className="border-top border-green-700 py-3 text-center text-white-50 small">
        © {new Date().getFullYear()} MediaHub
      </footer>
      */}
    </div>
  )
}

