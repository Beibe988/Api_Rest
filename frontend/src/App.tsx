import { Routes, Route } from 'react-router-dom'
import AppShell from './components/layout/AppShell'
import RegisterPage from './pages/RegisterPage'
import LoginPage from './pages/LoginPage'
import MoviesPage from './pages/MoviesPage'
import SeriesPage from './pages/SeriesPage'
import CategoriesPage from './pages/CategoriesPage'
import UsersPage from './pages/UsersPage'
import { GuestOnly, RequireAuth, RequireRoles } from './components/RouteGuards'
import ToastHost from './components/ToastHost'
import './styles/table.css'

function Home() {
  return (
    <div className="container py-4">
      <h1 className="h3">Home</h1>
      <p className="text-muted">Benvenuto! Usa la sidebar o la navbar per navigare.</p>
    </div>
  )
}

export default function App() {
  return (
    <>
      <AppShell>
        <Routes>
          <Route path="/" element={<Home />} />

          {/* Guest: solo Login e Register */}
          <Route path="/login" element={<GuestOnly><LoginPage /></GuestOnly>} />
          <Route path="/register" element={<GuestOnly><RegisterPage /></GuestOnly>} />

          {/* User + Admin: Movies, Series */}
          <Route
            path="/movies"
            element={
              <RequireRoles roles={['User','Admin']}>
                <MoviesPage />
              </RequireRoles>
            }
          />
          <Route
            path="/series"
            element={
              <RequireRoles roles={['User','Admin']}>
                <SeriesPage />
              </RequireRoles>
            }
          />

          {/* Solo Admin: Categories, Users */}
          <Route
            path="/categories"
            element={
              <RequireRoles roles={['Admin']}>
                <CategoriesPage />
              </RequireRoles>
            }
          />
          <Route
            path="/users"
            element={
              <RequireRoles roles={['Admin']}>
                <UsersPage />
              </RequireRoles>
            }
          />

          {/* Esempio di route autenticata generica (qualsiasi ruolo != Guest) */}
          <Route path="/account" element={<RequireAuth><div className="container py-4">Account</div></RequireAuth>} />
        </Routes>
      </AppShell>

      <ToastHost />
    </>
  )
}


