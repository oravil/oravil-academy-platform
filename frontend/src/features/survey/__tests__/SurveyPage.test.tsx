import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter, Route, Routes } from 'react-router-dom'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { SurveyPage } from '../SurveyPage'

const getSurvey = vi.fn()
const submitSurveyResponse = vi.fn()

vi.mock('@/lib/api', () => ({
  getSurvey: (...args: unknown[]) => getSurvey(...args),
  submitSurveyResponse: (...args: unknown[]) => submitSurveyResponse(...args),
  isApiError: (error: unknown) =>
    typeof error === 'object' && error !== null && 'status' in error && 'message' in error,
}))

const surveyFixture = {
  survey_id: 'survey-1',
  module_id: 'module-1',
  title: 'Post-Module Survey',
  questions: [
    {
      survey_question_id: 'q-rating',
      position: 1,
      question_text: 'How would you rate your learning experience in Module 1?',
      question_type: 'rating',
      required: true,
    },
    {
      survey_question_id: 'q-required-text',
      position: 2,
      question_text: 'What would you change or improve about this module?',
      question_type: 'text',
      required: true,
    },
    {
      survey_question_id: 'q-optional-text',
      position: 3,
      question_text: 'Is there anything else you want to share?',
      question_type: 'text',
      required: false,
    },
  ],
} as const

function renderPage(initialEntry = '/modules/module-1/survey') {
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  return render(
    <QueryClientProvider client={queryClient}>
      <MemoryRouter
        initialEntries={[initialEntry]}
        future={{ v7_startTransition: true, v7_relativeSplatPath: true }}
      >
        <Routes>
          <Route path="/" element={<div>Module Overview Page</div>} />
          <Route path="/modules/:moduleId/survey" element={<SurveyPage />} />
        </Routes>
      </MemoryRouter>
    </QueryClientProvider>
  )
}

async function fillValidAnswers(user: ReturnType<typeof userEvent.setup>) {
  await user.click(screen.getByRole('radio', { name: '5' }))
  await user.type(
    screen.getByLabelText('What would you change or improve about this module?'),
    'More real-world case studies would help.'
  )
}

describe('SurveyPage', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('shows a loading state while the survey request is in flight', () => {
    getSurvey.mockReturnValue(new Promise(() => {}))

    renderPage()

    expect(screen.getByText(/loading/i)).toBeInTheDocument()
  })

  it('renders the survey title and all three questions with a rating scale and text inputs', async () => {
    getSurvey.mockResolvedValue(surveyFixture)

    renderPage()

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: 'Post-Module Survey' })).toBeInTheDocument()
    })

    expect(
      screen.getByText('How would you rate your learning experience in Module 1?')
    ).toBeInTheDocument()
    expect(screen.getAllByRole('radio')).toHaveLength(5)
    expect(
      screen.getByLabelText('What would you change or improve about this module?')
    ).toBeInTheDocument()
    expect(screen.getByText('Is there anything else you want to share?')).toBeInTheDocument()
    expect(screen.getByText('(optional)')).toBeInTheDocument()
    expect(screen.getByRole('button', { name: /submit survey/i })).toBeEnabled()
  })

  it('provides a link back to Module Overview on the form screen', async () => {
    getSurvey.mockResolvedValue(surveyFixture)

    renderPage()

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: 'Post-Module Survey' })).toBeInTheDocument()
    })

    expect(screen.getByRole('link', { name: /back to module overview/i })).toHaveAttribute(
      'href',
      '/'
    )
  })

  it('rejects submission client-side when the required rating is missing, without calling the API', async () => {
    getSurvey.mockResolvedValue(surveyFixture)
    const user = userEvent.setup()

    renderPage()

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: 'Post-Module Survey' })).toBeInTheDocument()
    })

    await user.type(
      screen.getByLabelText('What would you change or improve about this module?'),
      'Some feedback.'
    )
    await user.click(screen.getByRole('button', { name: /submit survey/i }))

    expect(await screen.findByText('Please choose a rating.')).toBeInTheDocument()
    expect(submitSurveyResponse).not.toHaveBeenCalled()
  })

  it('rejects submission client-side when the required text question is empty, without calling the API', async () => {
    getSurvey.mockResolvedValue(surveyFixture)
    const user = userEvent.setup()

    renderPage()

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: 'Post-Module Survey' })).toBeInTheDocument()
    })

    await user.click(screen.getByRole('radio', { name: '4' }))
    await user.click(screen.getByRole('button', { name: /submit survey/i }))

    expect(await screen.findByText('This question requires an answer.')).toBeInTheDocument()
    expect(submitSurveyResponse).not.toHaveBeenCalled()
  })

  it('submits with the optional question omitted and shows the exact Screen 5 confirmation state, with no further action available', async () => {
    getSurvey.mockResolvedValue(surveyFixture)
    submitSurveyResponse.mockResolvedValue({
      survey_id: 'survey-1',
      submitted_at: '2026-07-23T10:00:00Z',
    })
    const user = userEvent.setup()

    renderPage()

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: 'Post-Module Survey' })).toBeInTheDocument()
    })

    await fillValidAnswers(user)
    await user.click(screen.getByRole('button', { name: /submit survey/i }))

    await waitFor(() => {
      expect(submitSurveyResponse).toHaveBeenCalledWith('survey-1', [
        { survey_question_id: 'q-rating', answer_rating: 5 },
        {
          survey_question_id: 'q-required-text',
          answer_text: 'More real-world case studies would help.',
        },
      ])
    })

    expect(await screen.findByRole('heading', { name: 'Module 1 Complete' })).toBeInTheDocument()
    expect(
      screen.getByText('Your feedback has been received. Thank you for completing Module 1.')
    ).toBeInTheDocument()
    expect(screen.queryByRole('link', { name: /back to module overview/i })).not.toBeInTheDocument()
    expect(screen.queryByRole('button', { name: /submit survey/i })).not.toBeInTheDocument()
  })

  it('includes the optional answer in the payload when it is filled in', async () => {
    getSurvey.mockResolvedValue(surveyFixture)
    submitSurveyResponse.mockResolvedValue({
      survey_id: 'survey-1',
      submitted_at: '2026-07-23T10:00:00Z',
    })
    const user = userEvent.setup()

    renderPage()

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: 'Post-Module Survey' })).toBeInTheDocument()
    })

    await fillValidAnswers(user)
    await user.type(
      screen.getByLabelText(/Is there anything else you want to share\?/),
      'No further comments.'
    )
    await user.click(screen.getByRole('button', { name: /submit survey/i }))

    await waitFor(() => {
      expect(submitSurveyResponse).toHaveBeenCalledWith('survey-1', [
        { survey_question_id: 'q-rating', answer_rating: 5 },
        {
          survey_question_id: 'q-required-text',
          answer_text: 'More real-world case studies would help.',
        },
        { survey_question_id: 'q-optional-text', answer_text: 'No further comments.' },
      ])
    })
  })

  it('shows a graceful "survey unavailable" state on a 403 when the module is not yet complete', async () => {
    getSurvey.mockRejectedValue({ status: 403, message: 'This module is not yet complete.' })

    renderPage()

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: /survey unavailable/i })).toBeInTheDocument()
    })

    expect(screen.getByText('This module is not yet complete.')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: /back to module overview/i })).toHaveAttribute(
      'href',
      '/'
    )
  })

  it('shows a graceful "survey unavailable" state on a 403 when the survey was already submitted', async () => {
    getSurvey.mockRejectedValue({ status: 403, message: 'This survey has already been submitted.' })

    renderPage()

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: /survey unavailable/i })).toBeInTheDocument()
    })

    expect(screen.getByText('This survey has already been submitted.')).toBeInTheDocument()
    expect(screen.getByRole('link', { name: /back to module overview/i })).toHaveAttribute(
      'href',
      '/'
    )
  })

  it('shows the generic error message (with a way back) when the survey fails to load for another reason', async () => {
    getSurvey.mockRejectedValue({ status: 404, message: 'Survey not found.' })

    renderPage()

    await waitFor(() => {
      expect(screen.getByText('Survey not found.')).toBeInTheDocument()
    })

    expect(screen.getByRole('link', { name: /back to module overview/i })).toHaveAttribute(
      'href',
      '/'
    )
  })

  it('shows a graceful "survey unavailable" state when submission comes back 403 (race backstop)', async () => {
    getSurvey.mockResolvedValue(surveyFixture)
    submitSurveyResponse.mockRejectedValue({
      status: 403,
      message: 'This survey has already been submitted.',
    })
    const user = userEvent.setup()

    renderPage()

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: 'Post-Module Survey' })).toBeInTheDocument()
    })

    await fillValidAnswers(user)
    await user.click(screen.getByRole('button', { name: /submit survey/i }))

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: /survey unavailable/i })).toBeInTheDocument()
    })
    expect(screen.getByText('This survey has already been submitted.')).toBeInTheDocument()
  })

  it('maps a 422 server validation error as a backstop when it survives client-side validation', async () => {
    getSurvey.mockResolvedValue(surveyFixture)
    submitSurveyResponse.mockRejectedValue({
      status: 422,
      message: 'The given data was invalid.',
      fields: [
        {
          field: 'answers',
          message: 'Question [q-rating] requires an integer rating between 1 and 5.',
        },
      ],
    })
    const user = userEvent.setup()

    renderPage()

    await waitFor(() => {
      expect(screen.getByRole('heading', { name: 'Post-Module Survey' })).toBeInTheDocument()
    })

    await fillValidAnswers(user)
    await user.click(screen.getByRole('button', { name: /submit survey/i }))

    expect(
      await screen.findByText('Question [q-rating] requires an integer rating between 1 and 5.')
    ).toBeInTheDocument()
  })
})
