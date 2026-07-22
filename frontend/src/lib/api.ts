const BASE_URL = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'

export interface ApiErrorField {
  field: string
  message: string
}

export interface ApiErrorShape {
  status: number
  code?: string
  message: string
  fields?: ApiErrorField[]
}

function getCookie(name: string): string | null {
  const value = document.cookie.split('; ').find((cookie) => cookie.startsWith(`${name}=`))

  return value ? decodeURIComponent(value.split('=').slice(1).join('=')) : null
}

function getXsrfToken(): string | null {
  return getCookie('XSRF-TOKEN')
}

async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    ...(options.headers as Record<string, string>),
  }

  const method = options.method?.toUpperCase() ?? 'GET'

  if (options.body !== undefined) {
    headers['Content-Type'] = 'application/json'
  }

  if (!['GET', 'HEAD', 'OPTIONS'].includes(method)) {
    const xsrfToken = getXsrfToken()

    if (xsrfToken) {
      headers['X-XSRF-TOKEN'] = xsrfToken
    }
  }

  const response = await fetch(`${BASE_URL}${path}`, {
    ...options,
    credentials: 'include',
    headers,
  })

  if (!response.ok) {
    const body = await response.json().catch(() => ({}))
    const error = body && typeof body === 'object' && 'error' in body ? body.error : {}
    throw {
      status: response.status,
      code: typeof error?.code === 'string' ? error.code : undefined,
      message: typeof error?.message === 'string' ? error.message : 'An unexpected error occurred.',
      fields: Array.isArray(error?.fields) ? error.fields : undefined,
    } satisfies ApiErrorShape
  }

  if (response.status === 204) {
    return undefined as T
  }

  return response.json() as Promise<T>
}

export interface LearnerResponse {
  learner_id: string
  email: string
  display_name: string
}

export function isApiError(error: unknown): error is ApiErrorShape {
  return typeof error === 'object' && error !== null && 'status' in error && 'message' in error
}

export async function getCsrfCookie(): Promise<void> {
  await fetch(`${BASE_URL}/sanctum/csrf-cookie`, {
    credentials: 'include',
    headers: {
      Accept: 'application/json',
    },
  })
}

export async function login(email: string, password: string): Promise<LearnerResponse> {
  await getCsrfCookie()

  return request<LearnerResponse>('/v1/auth/login', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  })
}

export function logout(): Promise<void> {
  return request<void>('/v1/auth/logout', { method: 'POST' })
}

export function getMe(): Promise<LearnerResponse> {
  return request<LearnerResponse>('/v1/auth/me')
}

export type LessonStatus = 'locked' | 'available' | 'complete'
export type ModuleStatus = 'in_progress' | 'complete'

export interface ModuleOverviewLessonResponse {
  lesson_id: string
  position: number
  title: string
  status: LessonStatus
}

export interface ModuleOverviewResponse {
  module_id: string
  title: string
  purpose: string
  deliverable_description: string
  lessons: ModuleOverviewLessonResponse[]
  module_status: ModuleStatus
}

export interface LearnerProgressResponse {
  module_id: string
  lessons_complete: number
  lessons_total: number
  current_lesson_id: string | null
  module_status: ModuleStatus
  survey_submitted: boolean
}

export function getModuleOverview(moduleId: string): Promise<ModuleOverviewResponse> {
  return request<ModuleOverviewResponse>(`/v1/modules/${moduleId}/overview`)
}

export function getLearnerProgress(moduleId: string): Promise<LearnerProgressResponse> {
  return request<LearnerProgressResponse>(`/v1/learners/me/progress/${moduleId}`)
}

export interface AssignmentDetailResponse {
  assignment_id: string
  deliverable_name: string
  prompt: string
  minimum_word_count: number | null
}

export interface LessonResponse {
  lesson_id: string
  module_id: string
  position: number
  title: string
  estimated_reading_minutes: number | null
  content: string
  assignment: AssignmentDetailResponse
}

export function getLesson(lessonId: string): Promise<LessonResponse> {
  return request<LessonResponse>(`/v1/lessons/${lessonId}`)
}
