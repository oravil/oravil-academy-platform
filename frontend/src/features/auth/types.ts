export interface Learner {
  learner_id: string
  email: string
  display_name: string
}

export interface AuthState {
  learner: Learner | null
  isAuthenticated: boolean
  isLoading: boolean
}
