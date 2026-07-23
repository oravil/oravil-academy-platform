import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen, waitFor } from '@testing-library/react'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ModuleCompletePage } from '../ModuleCompletePage'

const getModuleCompletion = vi.fn()

vi.mock('@/lib/api', () => ({
  getModuleCompletion: (...args: unknown[]) => getModuleCompletion(...args),
  isApiError: (error: unknown) =>
    typeof error === 'object' && error !== null && 'status' in error && 'message' in error,
}))

const completionFixture = {
  module_id: 'module-1',
  title: 'The Digital Marketing Landscape',
  deliverable_description: 'A completed Module 1 Landscape Map.',
  completed_lessons: [
    { lesson_id: 'lesson-1', position: 1, title: 'What is Digital Marketing' },
    { lesson_id: 'lesson-2', position: 2, title: 'Digital vs Traditional Marketing' },
    { lesson_id: 'lesson-3', position: 3, title: 'Core Digital Marketing Channels' },
    { lesson_id: 'lesson-4', position: 4, title: 'Digital Marketing Team Roles' },
  ],
  survey_submitted: false,
} as const

function renderPage(initialEntry = '/modules/module-1/completion') {
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  return render(
    <QueryClientProvider client={queryClient}>
      <MemoryRouter
        initialEntries={[initialEntry]}
        future={{ v7_startTransition: true, v7_relativeSplatPath: true }}
      >
        <Routes>
          <Route path="/" element={<div>Module Overview Page</div>} />
          <Route path="/modules/:moduleId/completion" element={<ModuleCompletePage />} />
        </Routes>
      </MemoryRouter>
    </QueryClientProvider>
  )
}

describe('ModuleCompletePage', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('shows a loading state while the completion request is in flight', () => {
    getModuleCompletion.mockReturnValue(new Promise(() => {}))

    renderPage()

    expect(screen.getByText(/loading/i)).toBeInTheDocument()
  })

  it('renders the module title, all four completed lessons, the deliverable, and a live link to the survey', async () => {
    getModuleCompletion.mockResolvedValue(completionFixture)

    renderPage()

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: 'Module Complete' })).toBeInTheDocument()
    })

    expect(screen.getByText(completionFixture.title)).toBeInTheDocument()
    completionFixture.completed_lessons.forEach((lesson) => {
      expect(screen.getByText(`Lesson ${lesson.position}: ${lesson.title}`)).toBeInTheDocument()
    })
    expect(screen.getAllByText('Complete')).toHaveLength(4)
    expect(screen.getByText(completionFixture.deliverable_description)).toBeInTheDocument()

    expect(screen.getByRole('link', { name: /proceed to post-module survey/i })).toHaveAttribute(
      'href',
      '/modules/module-1/survey'
    )
  })

  it('renders an acknowledged completed state instead of a link when the survey has already been submitted', async () => {
    getModuleCompletion.mockResolvedValue({ ...completionFixture, survey_submitted: true })

    renderPage()

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: 'Module Complete' })).toBeInTheDocument()
    })

    expect(
      screen.getByText('Post-module survey submitted — thank you for your feedback.')
    ).toBeInTheDocument()
    expect(
      screen.queryByRole('link', { name: /proceed to post-module survey/i })
    ).not.toBeInTheDocument()
    expect(
      screen.queryByRole('button', { name: /proceed to post-module survey/i })
    ).not.toBeInTheDocument()
  })

  it('provides a link back to Module Overview', async () => {
    getModuleCompletion.mockResolvedValue(completionFixture)

    renderPage()

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: 'Module Complete' })).toBeInTheDocument()
    })

    expect(screen.getByRole('link', { name: /back to module overview/i })).toHaveAttribute(
      'href',
      '/'
    )
  })

  it('shows a graceful "module not yet complete" state on a 403, with a way back', async () => {
    getModuleCompletion.mockRejectedValue({
      status: 403,
      message: 'This module is not yet complete.',
    })

    renderPage()

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: /module not yet complete/i })).toBeInTheDocument()
    })

    expect(screen.getByText('This module is not yet complete.')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: /back to module overview/i })).toHaveAttribute(
      'href',
      '/'
    )
  })

  it('shows the standard error envelope message for a non-403 failure', async () => {
    getModuleCompletion.mockRejectedValue({ status: 404, message: 'Module not found.' })

    renderPage()

    await waitFor(() => {
      expect(screen.getByText('Module not found.')).toBeInTheDocument()
    })

    expect(screen.getByRole('link', { name: /back to module overview/i })).toHaveAttribute(
      'href',
      '/'
    )
  })
})
