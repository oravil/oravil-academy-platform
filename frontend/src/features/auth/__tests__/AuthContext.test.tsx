import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { AuthProvider, useAuth } from '../AuthContext'
import { LoginPage } from '../LoginPage'
import { ProtectedRoute } from '../ProtectedRoute'

const getMe = vi.fn()
const login = vi.fn()
const logout = vi.fn()

vi.mock('@/lib/api', () => ({
  getMe: (...args: unknown[]) => getMe(...args),
  login: (...args: unknown[]) => login(...args),
  logout: (...args: unknown[]) => logout(...args),
  isApiError: (error: unknown) =>
    typeof error === 'object' && error !== null && 'status' in error && 'message' in error,
}))

function AuthActions() {
  const { learner, logout: signOut } = useAuth()

  return (
    <div>
      <div>Welcome {learner?.display_name}</div>
      <button type="button" onClick={() => signOut()}>
        Sign out
      </button>
    </div>
  )
}

function renderAuthApp(initialEntry = '/') {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: {
        retry: false,
      },
    },
  })

  return render(
    <QueryClientProvider client={queryClient}>
      <MemoryRouter
        initialEntries={[initialEntry]}
        future={{ v7_startTransition: true, v7_relativeSplatPath: true }}
      >
        <AuthProvider>
          <Routes>
            <Route path="/login" element={<LoginPage />} />
            <Route element={<ProtectedRoute />}>
              <Route path="/" element={<AuthActions />} />
            </Route>
          </Routes>
        </AuthProvider>
      </MemoryRouter>
    </QueryClientProvider>
  )
}

describe('AuthProvider', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    vi.spyOn(Storage.prototype, 'setItem')
    vi.spyOn(Storage.prototype, 'removeItem')
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  it('retrieves authenticated learner state from /v1/auth/me', async () => {
    getMe.mockResolvedValue({
      learner_id: '0f8fad5b-d9cb-469f-a165-70867728950e',
      email: 'learner@example.com',
      display_name: 'Learner One',
    })

    renderAuthApp()

    await waitFor(() => {
      expect(getMe).toHaveBeenCalledTimes(1)
      expect(screen.getByText('Welcome Learner One')).toBeInTheDocument()
    })
  })

  it('redirects unauthenticated users based on /v1/auth/me server state', async () => {
    getMe.mockRejectedValue({ status: 401, message: 'Authentication required.' })

    renderAuthApp()

    await waitFor(() => {
      expect(getMe).toHaveBeenCalledTimes(1)
      expect(screen.getByRole('button', { name: /sign in/i })).toBeInTheDocument()
    })
  })

  it('does not persist bearer tokens in localStorage during login', async () => {
    const user = userEvent.setup()

    getMe
      .mockRejectedValueOnce({ status: 401, message: 'Authentication required.' })
      .mockResolvedValueOnce({
        learner_id: '0f8fad5b-d9cb-469f-a165-70867728950e',
        email: 'learner@example.com',
        display_name: 'Learner One',
      })
    login.mockResolvedValue(undefined)

    renderAuthApp('/login')

    await user.type(screen.getByLabelText(/email/i), 'learner@example.com')
    await user.type(screen.getByLabelText(/password/i), 'password')
    await user.click(screen.getByRole('button', { name: /sign in/i }))

    await waitFor(() => {
      expect(login).toHaveBeenCalledWith('learner@example.com', 'password')
      expect(screen.getByText('Welcome Learner One')).toBeInTheDocument()
    })

    expect(localStorage.setItem).not.toHaveBeenCalled()
    expect(localStorage.removeItem).not.toHaveBeenCalled()
  })

  it('clears client auth state after logout', async () => {
    const user = userEvent.setup()

    getMe.mockResolvedValue({
      learner_id: '0f8fad5b-d9cb-469f-a165-70867728950e',
      email: 'learner@example.com',
      display_name: 'Learner One',
    })
    logout.mockResolvedValue(undefined)

    renderAuthApp()

    await screen.findByText('Welcome Learner One')
    await user.click(screen.getByRole('button', { name: /sign out/i }))

    await waitFor(() => {
      expect(logout).toHaveBeenCalledTimes(1)
      expect(screen.getByRole('button', { name: /sign in/i })).toBeInTheDocument()
    })

    expect(localStorage.removeItem).not.toHaveBeenCalled()
  })
})
