import * as React from 'react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { getMe, isApiError, login as apiLogin, logout as apiLogout } from '@/lib/api'
import type { AuthState, Learner } from './types'

interface AuthContextValue extends AuthState {
  login: (email: string, password: string) => Promise<void>
  logout: () => Promise<void>
}

const AuthContext = React.createContext<AuthContextValue | undefined>(undefined)
export const AUTH_QUERY_KEY = ['auth', 'learner'] as const

async function fetchAuthenticatedLearner(): Promise<Learner | null> {
  try {
    return await getMe()
  } catch (error: unknown) {
    if (isApiError(error) && error.status === 401) {
      return null
    }

    throw error
  }
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const queryClient = useQueryClient()
  const { data: learner = null, isLoading } = useQuery({
    queryKey: AUTH_QUERY_KEY,
    queryFn: fetchAuthenticatedLearner,
    retry: false,
  })

  const login = React.useCallback(
    async (email: string, password: string) => {
      await apiLogin(email, password)
      const authenticatedLearner = await fetchAuthenticatedLearner()
      queryClient.setQueryData(AUTH_QUERY_KEY, authenticatedLearner)
    },
    [queryClient]
  )

  const logout = React.useCallback(async () => {
    try {
      await apiLogout()
    } finally {
      queryClient.setQueryData(AUTH_QUERY_KEY, null)
    }
  }, [queryClient])

  const value: AuthContextValue = {
    learner,
    isAuthenticated: learner !== null,
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
