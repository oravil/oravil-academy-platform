import * as React from 'react'
import { getMe, login as apiLogin, logout as apiLogout } from '@/lib/api'
import type { AuthState, User } from './types'

interface AuthContextValue extends AuthState {
  login: (email: string, password: string) => Promise<void>
  logout: () => Promise<void>
}

const AuthContext = React.createContext<AuthContextValue | undefined>(undefined)

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = React.useState<User | null>(null)
  const [token, setToken] = React.useState<string | null>(null)
  const [isLoading, setIsLoading] = React.useState(true)

  React.useEffect(() => {
    const storedToken = localStorage.getItem('auth_token')
    if (!storedToken) {
      setIsLoading(false)
      return
    }
    setToken(storedToken)
    getMe()
      .then((u) => setUser(u))
      .catch(() => {
        localStorage.removeItem('auth_token')
        setToken(null)
      })
      .finally(() => setIsLoading(false))
  }, [])

  const login = React.useCallback(async (email: string, password: string) => {
    const data = await apiLogin(email, password)
    localStorage.setItem('auth_token', data.token)
    setToken(data.token)
    setUser(data.user)
  }, [])

  const logout = React.useCallback(async () => {
    try {
      await apiLogout()
    } finally {
      localStorage.removeItem('auth_token')
      setToken(null)
      setUser(null)
    }
  }, [])

  const value: AuthContextValue = {
    user,
    token,
    isAuthenticated: user !== null,
    isLoading,
    login,
    logout,
  }

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth(): AuthContextValue {
  const ctx = React.useContext(AuthContext)
  if (!ctx) {
    throw new Error('useAuth must be used within an AuthProvider')
  }
  return ctx
}
