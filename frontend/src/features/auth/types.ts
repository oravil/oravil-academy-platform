export interface Learner {
  id: string
  email: string
  display_name: string
  enrolled_at: string
}

export interface AuthState {
  learner: Learner | null
  isAuthenticated: boolean
  isLoading: boolean
}
