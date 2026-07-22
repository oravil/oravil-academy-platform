import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { AssignmentSubmissionPage } from '../AssignmentSubmissionPage'

const getLesson = vi.fn()
const submitAssignment = vi.fn()

vi.mock('@/lib/api', () => ({
  getLesson: (...args: unknown[]) => getLesson(...args),
  submitAssignment: (...args: unknown[]) => submitAssignment(...args),
  isApiError: (error: unknown) =>
    typeof error === 'object' && error !== null && 'status' in error && 'message' in error,
}))

const lessonFixture = {
  lesson_id: 'lesson-1',
  module_id: 'module-1',
  position: 1,
  title: 'What Is Digital Marketing',
  estimated_reading_minutes: 12,
  content: '# What Is Digital Marketing\n\nSome introductory paragraph.',
  assignment: {
    assignment_id: 'assignment-1',
    deliverable_name: 'My Definition of Digital Marketing',
    prompt: 'A written definition of digital marketing (maximum 75 words).',
    minimum_word_count: null,
  },
} as const

function renderPage(initialEntry = '/lessons/lesson-1/assignment') {
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  return render(
    <QueryClientProvider client={queryClient}>
      <MemoryRouter
        initialEntries={[initialEntry]}
        future={{ v7_startTransition: true, v7_relativeSplatPath: true }}
      >
        <Routes>
          <Route path="/" element={<div>Module Overview Page</div>} />
          <Route path="/lessons/:lessonId/assignment" element={<AssignmentSubmissionPage />} />
        </Routes>
      </MemoryRouter>
    </QueryClientProvider>
  )
}

describe('AssignmentSubmissionPage', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('shows a loading state while the lesson request is in flight', () => {
    getLesson.mockReturnValue(new Promise(() => {}))

    renderPage()

    expect(screen.getByText(/loading/i)).toBeInTheDocument()
  })

  it('renders lesson context, the assignment prompt, the response textarea, and the submit action', async () => {
    getLesson.mockResolvedValue(lessonFixture)

    renderPage()

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: lessonFixture.title })).toBeInTheDocument()
    })

    expect(screen.getByText('Lesson 1')).toBeInTheDocument()
    expect(
      screen.getByText(`Assignment: ${lessonFixture.assignment.deliverable_name}`)
    ).toBeInTheDocument()
    expect(screen.getByText(lessonFixture.assignment.prompt)).toBeInTheDocument()
    expect(screen.getByLabelText(/your response/i)).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /submit assignment/i })).toBeEnabled()
  })

  it('rejects empty content client-side with the approved wireframe message, without calling the API', async () => {
    getLesson.mockResolvedValue(lessonFixture)
    const user = userEvent.setup()

    renderPage()

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: lessonFixture.title })).toBeInTheDocument()
    })

    await user.click(screen.getByRole('button', { name: /submit assignment/i }))

    expect(
      await screen.findByText(
        'Your assignment is empty. Please write your response before submitting.'
      )
    ).toBeInTheDocument()
    expect(submitAssignment).not.toHaveBeenCalled()
  })

  it("rejects content below the assignment's minimum word count client-side, without calling the API", async () => {
    getLesson.mockResolvedValue({
      ...lessonFixture,
      assignment: { ...lessonFixture.assignment, minimum_word_count: 20 },
    })
    const user = userEvent.setup()

    renderPage()

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: lessonFixture.title })).toBeInTheDocument()
    })

    await user.type(screen.getByLabelText(/your response/i), 'Too short.')
    await user.click(screen.getByRole('button', { name: /submit assignment/i }))

    expect(
      await screen.findByText(
        'Your response is shorter than the minimum required. Please complete your answer.'
      )
    ).toBeInTheDocument()
    expect(submitAssignment).not.toHaveBeenCalled()
  })

  it('submits the assignment and navigates to the Module Overview on success', async () => {
    getLesson.mockResolvedValue(lessonFixture)
    submitAssignment.mockResolvedValue({
      submission_id: 'submission-1',
      assignment_id: 'assignment-1',
      status: 'submitted',
      submitted_at: '2026-07-22T21:00:00Z',
    })
    const user = userEvent.setup()

    renderPage()

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: lessonFixture.title })).toBeInTheDocument()
    })

    await user.type(
      screen.getByLabelText(/your response/i),
      'A thorough, thoughtful reflection on digital marketing fundamentals.'
    )
    await user.click(screen.getByRole('button', { name: /submit assignment/i }))

    await waitFor(() => {
      expect(submitAssignment).toHaveBeenCalledWith(
        'assignment-1',
        'A thorough, thoughtful reflection on digital marketing fundamentals.'
      )
    })

    expect(await screen.findByText('Module Overview Page')).toBeInTheDocument()
  })

  it('maps a 422 server validation error onto the content field', async () => {
    getLesson.mockResolvedValue(lessonFixture)
    submitAssignment.mockRejectedValue({
      status: 422,
      message: 'The given data was invalid.',
      fields: [
        {
          field: 'content',
          message:
            'Your response is shorter than the minimum required. Please complete your answer.',
        },
      ],
    })
    const user = userEvent.setup()

    renderPage()

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: lessonFixture.title })).toBeInTheDocument()
    })

    await user.type(screen.getByLabelText(/your response/i), 'A short but non-empty response.')
    await user.click(screen.getByRole('button', { name: /submit assignment/i }))

    expect(
      await screen.findByText(
        'Your response is shorter than the minimum required. Please complete your answer.'
      )
    ).toBeInTheDocument()
  })

  it('shows a graceful "submission unavailable" state on a 403 (already submitted), instead of a raw error', async () => {
    getLesson.mockResolvedValue(lessonFixture)
    submitAssignment.mockRejectedValue({
      status: 403,
      message: 'This assignment has already been submitted.',
    })
    const user = userEvent.setup()

    renderPage()

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: lessonFixture.title })).toBeInTheDocument()
    })

    await user.type(screen.getByLabelText(/your response/i), 'A perfectly reasonable response.')
    await user.click(screen.getByRole('button', { name: /submit assignment/i }))

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: /submission unavailable/i })).toBeInTheDocument()
    })
    expect(screen.getByText('This assignment has already been submitted.')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: /back to module overview/i })).toHaveAttribute(
      'href',
      '/'
    )
  })

  it('shows a locked state with a way back to the Overview when the underlying lesson is locked', async () => {
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

  it('shows the generic error message (with a way back) when the lesson fails to load for another reason', async () => {
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
