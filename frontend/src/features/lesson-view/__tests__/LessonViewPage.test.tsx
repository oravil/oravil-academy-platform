import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen, waitFor } from '@testing-library/react'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { LessonViewPage } from '../LessonViewPage'

const getLesson = vi.fn()

vi.mock('@/lib/api', () => ({
  getLesson: (...args: unknown[]) => getLesson(...args),
  isApiError: (error: unknown) =>
    typeof error === 'object' && error !== null && 'status' in error && 'message' in error,
}))

const lessonFixture = {
  lesson_id: 'lesson-1',
  module_id: 'module-1',
  position: 1,
  title: 'What Is Digital Marketing',
  estimated_reading_minutes: 12,
  content:
    '# What Is Digital Marketing\n\n## Core Concepts\n\nSome introductory paragraph.\n\n| Beginner | Professional |\n|---|---|\n| Do it all | Be strategic |\n',
  assignment: {
    assignment_id: 'assignment-1',
    deliverable_name: 'My Definition of Digital Marketing',
    prompt: 'A written definition of digital marketing (maximum 75 words).',
    minimum_word_count: null,
  },
} as const

function renderPage(initialEntry = '/lessons/lesson-1') {
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  return render(
    <QueryClientProvider client={queryClient}>
      <MemoryRouter
        initialEntries={[initialEntry]}
        future={{ v7_startTransition: true, v7_relativeSplatPath: true }}
      >
        <Routes>
          <Route path="/lessons/:lessonId" element={<LessonViewPage />} />
        </Routes>
      </MemoryRouter>
    </QueryClientProvider>
  )
}

describe('LessonViewPage', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('shows a loading state while the lesson request is in flight', () => {
    getLesson.mockReturnValue(new Promise(() => {}))

    renderPage()

    expect(screen.getByText(/loading/i)).toBeInTheDocument()
  })

  it('renders the lesson title, metadata, markdown content (including a table), and the assignment block', async () => {
    getLesson.mockResolvedValue(lessonFixture)

    renderPage()

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: lessonFixture.title })).toBeInTheDocument()
    })

    expect(screen.getByText('Lesson 1')).toBeInTheDocument()
    expect(screen.getByText('12 min read')).toBeInTheDocument()
    expect(screen.getByText('Some introductory paragraph.')).toBeInTheDocument()
    expect(screen.getByRole('table')).toBeInTheDocument()
    expect(screen.getByText('Be strategic')).toBeInTheDocument()

    expect(
      screen.getByText(`Assignment: ${lessonFixture.assignment.deliverable_name}`)
    ).toBeInTheDocument()
    expect(screen.getByText(lessonFixture.assignment.prompt)).toBeInTheDocument()

    expect(
      screen.getByRole('link', { name: /proceed to assignment submission/i })
    ).toHaveAttribute('href', '/lessons/lesson-1/assignment')
  })

  it('shows a locked state with a way back to the Overview when the lesson is locked', async () => {
    getLesson.mockRejectedValue({ status: 403, message: 'This lesson is locked.' })

    renderPage()

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: /this lesson is locked/i })).toBeInTheDocument()
    })

    expect(screen.getByText('This lesson is locked.')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: /back to module overview/i })).toHaveAttribute(
      'href',
      '/'
    )
  })

  it('shows the generic error message (with a way back) for a non-locked failure', async () => {
    getLesson.mockRejectedValue({ status: 404, message: 'Lesson not found.' })

    renderPage()

    await waitFor(() => {
      expect(screen.getByText('Lesson not found.')).toBeInTheDocument()
    })

    expect(screen.getByRole('link', { name: /back to module overview/i })).toHaveAttribute(
      'href',
      '/'
    )
  })
})
