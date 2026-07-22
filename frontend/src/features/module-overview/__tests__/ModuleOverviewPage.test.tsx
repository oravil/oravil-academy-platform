import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen, waitFor } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ModuleOverviewPage } from '../ModuleOverviewPage'

const getModuleOverview = vi.fn()
const getLearnerProgress = vi.fn()

vi.mock('@/lib/api', () => ({
  getModuleOverview: (...args: unknown[]) => getModuleOverview(...args),
  getLearnerProgress: (...args: unknown[]) => getLearnerProgress(...args),
  isApiError: (error: unknown) =>
    typeof error === 'object' && error !== null && 'status' in error && 'message' in error,
}))

const overviewFixture = {
  module_id: 'a3f3b6b0-0000-4000-8000-000000000001',
  title: 'The Digital Marketing Landscape',
  purpose: 'Establish a shared understanding of what digital marketing is.',
  deliverable_description: 'A completed Module 1 Landscape Map.',
  lessons: [
    { lesson_id: 'lesson-1', position: 1, title: 'What is Digital Marketing', status: 'available' },
    {
      lesson_id: 'lesson-2',
      position: 2,
      title: 'Digital vs Traditional Marketing',
      status: 'locked',
    },
    {
      lesson_id: 'lesson-3',
      position: 3,
      title: 'Core Digital Marketing Channels',
      status: 'locked',
    },
    { lesson_id: 'lesson-4', position: 4, title: 'Digital Marketing Team Roles', status: 'locked' },
  ],
  module_status: 'in_progress',
} as const

const progressFixture = {
  module_id: overviewFixture.module_id,
  lessons_complete: 0,
  lessons_total: 4,
  current_lesson_id: null,
  module_status: 'in_progress',
  survey_submitted: false,
} as const

function renderPage() {
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  return render(
    <QueryClientProvider client={queryClient}>
      <MemoryRouter future={{ v7_startTransition: true, v7_relativeSplatPath: true }}>
        <ModuleOverviewPage />
      </MemoryRouter>
    </QueryClientProvider>
  )
}

describe('ModuleOverviewPage', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('shows a loading state while the overview and progress requests are in flight', () => {
    getModuleOverview.mockReturnValue(new Promise(() => {}))
    getLearnerProgress.mockReturnValue(new Promise(() => {}))

    renderPage()

    expect(screen.getByText(/loading/i)).toBeInTheDocument()
  })

  it('renders module title, purpose, deliverable, and the four-lesson list with distinct status labels', async () => {
    getModuleOverview.mockResolvedValue(overviewFixture)
    getLearnerProgress.mockResolvedValue(progressFixture)

    renderPage()

    await waitFor(() => {
      expect(screen.getByText('The Digital Marketing Landscape')).toBeInTheDocument()
    })

    expect(screen.getByText(overviewFixture.purpose)).toBeInTheDocument()
    expect(screen.getByText(overviewFixture.deliverable_description)).toBeInTheDocument()
    expect(screen.getAllByRole('listitem')).toHaveLength(4)
    expect(screen.getByText('Available')).toBeInTheDocument()
    expect(screen.getAllByText('Locked')).toHaveLength(3)
  })

  it('renders locked lesson rows as non-interactive and available/complete rows as links', async () => {
    getModuleOverview.mockResolvedValue(overviewFixture)
    getLearnerProgress.mockResolvedValue(progressFixture)

    renderPage()

    await waitFor(() => {
      expect(screen.getByText('The Digital Marketing Landscape')).toBeInTheDocument()
    })

    expect(
      screen.getByRole('link', { name: /lesson 1: what is digital marketing/i })
    ).toHaveAttribute('href', '/lessons/lesson-1')
    expect(
      screen.queryByRole('link', { name: /lesson 2: digital vs traditional marketing/i })
    ).not.toBeInTheDocument()
  })

  it('navigates to Lesson 1 via the primary action on first access', async () => {
    getModuleOverview.mockResolvedValue(overviewFixture)
    getLearnerProgress.mockResolvedValue(progressFixture)

    renderPage()

    await waitFor(() => {
      expect(screen.getByRole('link', { name: /begin lesson 1/i })).toBeInTheDocument()
    })

    expect(screen.getByRole('link', { name: /begin lesson 1/i })).toHaveAttribute(
      'href',
      '/lessons/lesson-1'
    )
  })

  it('navigates to the current lesson via "Continue to Lesson N" once a lesson has been completed', async () => {
    getModuleOverview.mockResolvedValue({
      ...overviewFixture,
      lessons: [
        { ...overviewFixture.lessons[0], status: 'complete' },
        { ...overviewFixture.lessons[1], status: 'available' },
        overviewFixture.lessons[2],
        overviewFixture.lessons[3],
      ],
    })
    getLearnerProgress.mockResolvedValue({
      ...progressFixture,
      lessons_complete: 1,
      current_lesson_id: 'lesson-2',
    })

    renderPage()

    await waitFor(() => {
      expect(screen.getByRole('link', { name: /continue to lesson 2/i })).toBeInTheDocument()
    })

    expect(screen.getByRole('link', { name: /continue to lesson 2/i })).toHaveAttribute(
      'href',
      '/lessons/lesson-2'
    )
    expect(
      screen.getByRole('link', { name: /lesson 1: what is digital marketing/i })
    ).toHaveAttribute('href', '/lessons/lesson-1')
  })

  it('links to the Module Complete screen once the module status is complete', async () => {
    getModuleOverview.mockResolvedValue({
      ...overviewFixture,
      lessons: overviewFixture.lessons.map((lesson) => ({ ...lesson, status: 'complete' })),
      module_status: 'complete',
    })
    getLearnerProgress.mockResolvedValue({
      ...progressFixture,
      lessons_complete: 4,
      current_lesson_id: null,
      module_status: 'complete',
    })

    renderPage()

    await waitFor(() => {
      expect(screen.getByRole('link', { name: /proceed to module complete/i })).toBeInTheDocument()
    })

    expect(screen.getByRole('link', { name: /proceed to module complete/i })).toHaveAttribute(
      'href',
      `/modules/${overviewFixture.module_id}/completion`
    )
  })

  it('shows the standard error envelope message when the overview request fails', async () => {
    getModuleOverview.mockRejectedValue({ status: 404, message: 'Module not found.' })
    getLearnerProgress.mockResolvedValue(progressFixture)

    renderPage()

    await waitFor(() => {
      expect(screen.getByText('Module not found.')).toBeInTheDocument()
    })
  })

  it('shows the standard error envelope message when the progress request fails', async () => {
    getModuleOverview.mockResolvedValue(overviewFixture)
    getLearnerProgress.mockRejectedValue({ status: 401, message: 'Authentication required.' })

    renderPage()

    await waitFor(() => {
      expect(screen.getByText('Authentication required.')).toBeInTheDocument()
    })
  })
})
