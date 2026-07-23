import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { useQuery } from '@tanstack/react-query'
import { Link, useParams } from 'react-router-dom'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import {
  getSurvey,
  isApiError,
  submitSurveyResponse,
  type SurveyAnswerInput,
  type SurveyQuestionResponse,
} from '@/lib/api'

function BackToOverviewLink() {
  return (
    <Link to="/" className="text-sm text-primary hover:underline">
      ← Back to Module Overview
    </Link>
  )
}

const RATING_VALUES = [1, 2, 3, 4, 5] as const

interface SurveyFormValues {
  answers: Record<string, string>
}

function toAnswerInputs(
  questions: SurveyQuestionResponse[],
  answers: Record<string, string>
): SurveyAnswerInput[] {
  return questions
    .map((question): SurveyAnswerInput | null => {
      const answer = answers[question.survey_question_id]?.trim() ?? ''

      if (answer === '') {
        return null
      }

      return question.question_type === 'rating'
        ? { survey_question_id: question.survey_question_id, answer_rating: Number(answer) }
        : { survey_question_id: question.survey_question_id, answer_text: answer }
    })
    .filter((answer): answer is SurveyAnswerInput => answer !== null)
}

function RatingQuestion({
  question,
  register,
}: {
  question: SurveyQuestionResponse
  register: ReturnType<typeof useForm<SurveyFormValues>>['register']
}) {
  return (
    <div className="flex items-center justify-between gap-2">
      <span className="text-xs text-muted-foreground">1: Poor</span>
      <div className="flex gap-3" role="radiogroup" aria-label={question.question_text}>
        {RATING_VALUES.map((rating) => (
          <label key={rating} className="flex flex-col items-center gap-1 text-xs">
            <input
              type="radio"
              value={rating}
              {...register(`answers.${question.survey_question_id}`)}
            />
            {rating}
          </label>
        ))}
      </div>
      <span className="text-xs text-muted-foreground">5: Excellent</span>
    </div>
  )
}

export function SurveyPage() {
  const { moduleId } = useParams<{ moduleId: string }>()
  const [submitError, setSubmitError] = useState<string | null>(null)
  const [submitted, setSubmitted] = useState(false)

  const surveyQuery = useQuery({
    queryKey: ['survey', moduleId],
    queryFn: () => getSurvey(moduleId as string),
    retry: false,
    enabled: Boolean(moduleId),
  })

  const {
    register,
    handleSubmit,
    setError,
    clearErrors,
    formState: { errors, isSubmitting },
  } = useForm<SurveyFormValues>({ defaultValues: { answers: {} } })

  if (surveyQuery.isLoading) {
    return <div className="p-8">Loading…</div>
  }

  if (surveyQuery.isError) {
    const error = surveyQuery.error

    if (isApiError(error) && error.status === 403) {
      return (
        <div className="mx-auto max-w-2xl space-y-4 p-8">
          <BackToOverviewLink />
          <div className="space-y-2 rounded-md border p-6 text-center">
            <h1 className="text-lg font-semibold">Survey unavailable</h1>
            <p className="text-sm text-muted-foreground">{error.message}</p>
          </div>
        </div>
      )
    }

    const message = isApiError(error)
      ? error.message
      : 'Unable to load the survey. Please try again.'

    return (
      <div className="mx-auto max-w-2xl space-y-4 p-8">
        <BackToOverviewLink />
        <p className="text-red-600">{message}</p>
      </div>
    )
  }

  const survey = surveyQuery.data

  if (!survey) {
    return null
  }

  // OA-MVP-004 Screen 5: after submission the learner sees a single
  // confirmation message and no further action — no back link, no button.
  if (submitted) {
    return (
      <div className="mx-auto max-w-2xl space-y-4 p-8 text-center">
        <h1 className="text-2xl font-semibold">Module 1 Complete</h1>
        <p className="text-muted-foreground">
          Your feedback has been received. Thank you for completing Module 1.
        </p>
      </div>
    )
  }

  if (submitError !== null) {
    return (
      <div className="mx-auto max-w-2xl space-y-4 p-8">
        <BackToOverviewLink />
        <div className="space-y-2 rounded-md border p-6 text-center">
          <h1 className="text-lg font-semibold">Survey unavailable</h1>
          <p className="text-sm text-muted-foreground">{submitError}</p>
        </div>
      </div>
    )
  }

  // Mirrors the server's dynamic validation (SubmitSurveyResponse::validateAnswers,
  // OA-MVP-007) so the common case never round-trips a 422: required questions
  // must have an answer, ratings must be 1-5, required text must be non-empty.
  const onSubmit = async (values: SurveyFormValues) => {
    clearErrors()
    let valid = true

    survey.questions.forEach((question) => {
      const answer = values.answers[question.survey_question_id]?.trim() ?? ''

      if (question.required && answer === '') {
        setError(`answers.${question.survey_question_id}`, {
          message:
            question.question_type === 'rating'
              ? 'Please choose a rating.'
              : 'This question requires an answer.',
        })
        valid = false

        return
      }

      if (question.question_type === 'rating' && answer !== '') {
        const rating = Number(answer)

        if (!Number.isInteger(rating) || rating < 1 || rating > 5) {
          setError(`answers.${question.survey_question_id}`, {
            message: 'Please choose a rating between 1 and 5.',
          })
          valid = false
        }
      }
    })

    if (!valid) {
      return
    }

    try {
      await submitSurveyResponse(survey.survey_id, toAnswerInputs(survey.questions, values.answers))
      setSubmitted(true)
    } catch (err: unknown) {
      if (isApiError(err) && err.status === 403) {
        setSubmitError(err.message)

        return
      }

      if (isApiError(err) && Array.isArray(err.fields) && err.fields.length > 0) {
        setError('root', { message: err.fields.map((field) => field.message).join(' ') })

        return
      }

      setError('root', {
        message: isApiError(err) ? err.message : 'An error occurred. Please try again.',
      })
    }
  }

  return (
    <div className="mx-auto max-w-2xl space-y-6 p-8">
      <BackToOverviewLink />

      <div className="space-y-2">
        <h1 className="text-2xl font-semibold">{survey.title}</h1>
        <p className="text-muted-foreground">
          Module 1 is complete — share a few thoughts on your experience before you finish.
        </p>
      </div>

      <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-6">
        {survey.questions.map((question) => (
          <div key={question.survey_question_id} className="space-y-2">
            <label className="text-sm font-medium" htmlFor={question.survey_question_id}>
              {question.question_text}
              {!question.required && (
                <span className="ml-1 text-xs font-normal text-muted-foreground">(optional)</span>
              )}
            </label>

            {question.question_type === 'rating' ? (
              <RatingQuestion question={question} register={register} />
            ) : (
              <Textarea
                id={question.survey_question_id}
                rows={4}
                {...register(`answers.${question.survey_question_id}`)}
              />
            )}

            {errors.answers?.[question.survey_question_id] && (
              <p className="text-sm text-red-600">
                {errors.answers[question.survey_question_id]?.message}
              </p>
            )}
          </div>
        ))}

        {errors.root && <p className="text-sm text-red-600">{errors.root.message}</p>}

        <Button type="submit" className="w-full" disabled={isSubmitting}>
          {isSubmitting ? 'Submitting…' : 'Submit survey'}
        </Button>
      </form>
    </div>
  )
}
