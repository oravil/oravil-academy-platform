import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { useQuery } from '@tanstack/react-query'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { getLesson, isApiError, submitAssignment } from '@/lib/api'

function BackToOverviewLink() {
  return (
    <Link to="/" className="text-sm text-primary hover:underline">
      ← Back to Module Overview
    </Link>
  )
}

function wordCount(text: string): number {
  const trimmed = text.trim()

  return trimmed === '' ? 0 : trimmed.split(/\s+/).length
}

interface AssignmentSubmissionFormValues {
  content: string
}

export function AssignmentSubmissionPage() {
  const { lessonId } = useParams<{ lessonId: string }>()
  const navigate = useNavigate()
  // Set only when a submission attempt comes back 403 (already submitted, or
  // — reachable only if this screen is revisited after gating state changed —
  // not yet unlocked). Replaces the form with a graceful terminal state
  // rather than surfacing a raw error, per VS-004 Phase C scope.
  const [blockedMessage, setBlockedMessage] = useState<string | null>(null)

  const lessonQuery = useQuery({
    queryKey: ['lesson', lessonId],
    queryFn: () => getLesson(lessonId as string),
    retry: false,
    enabled: Boolean(lessonId),
  })

  const {
    register,
    handleSubmit,
    watch,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<AssignmentSubmissionFormValues>({ defaultValues: { content: '' } })

  const content = watch('content')

  if (lessonQuery.isLoading) {
    return <div className="p-8">Loading…</div>
  }

  if (lessonQuery.isError) {
    const error = lessonQuery.error

    if (isApiError(error) && error.status === 403) {
      return (
        <div className="mx-auto max-w-2xl space-y-4 p-8">
          <BackToOverviewLink />
          <div className="space-y-2 rounded-md border p-6 text-center">
            <h1 className="text-lg font-semibold">This lesson is locked</h1>
            <p className="text-sm text-muted-foreground">{error.message}</p>
          </div>
        </div>
      )
    }

    const message = isApiError(error)
      ? error.message
      : 'Unable to load this assignment. Please try again.'

    return (
      <div className="mx-auto max-w-2xl space-y-4 p-8">
        <BackToOverviewLink />
        <p className="text-red-600">{message}</p>
      </div>
    )
  }

  const lesson = lessonQuery.data

  if (!lesson) {
    return null
  }

  if (blockedMessage !== null) {
    return (
      <div className="mx-auto max-w-2xl space-y-4 p-8">
        <BackToOverviewLink />
        <div className="space-y-2 rounded-md border p-6 text-center">
          <h1 className="text-lg font-semibold">Submission unavailable</h1>
          <p className="text-sm text-muted-foreground">{blockedMessage}</p>
        </div>
      </div>
    )
  }

  const onSubmit = async (values: AssignmentSubmissionFormValues) => {
    const trimmed = values.content.trim()

    if (trimmed === '') {
      setError('content', {
        message: 'Your assignment is empty. Please write your response before submitting.',
      })

      return
    }

    if (
      lesson.assignment.minimum_word_count !== null &&
      wordCount(values.content) < lesson.assignment.minimum_word_count
    ) {
      setError('content', {
        message: 'Your response is shorter than the minimum required. Please complete your answer.',
      })

      return
    }

    try {
      await submitAssignment(lesson.assignment.assignment_id, values.content)
      navigate('/')
    } catch (err: unknown) {
      if (isApiError(err) && err.status === 403) {
        setBlockedMessage(err.message)

        return
      }

      if (isApiError(err) && Array.isArray(err.fields)) {
        err.fields.forEach((fieldError) => {
          if (fieldError.field === 'content') {
            setError('content', { message: fieldError.message })
          }
        })

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

      <div className="space-y-1">
        <p className="text-sm font-medium text-muted-foreground">Lesson {lesson.position}</p>
        <h1 className="text-2xl font-semibold">{lesson.title}</h1>
        <p className="text-sm text-muted-foreground">
          Assignment: {lesson.assignment.deliverable_name}
        </p>
      </div>

      <div className="space-y-2 rounded-md border p-4">
        <h2 className="text-sm font-medium">Assignment Prompt</h2>
        <p className="text-sm text-muted-foreground">{lesson.assignment.prompt}</p>
      </div>

      <form onSubmit={handleSubmit(onSubmit)} noValidate className="space-y-3">
        <div className="space-y-1">
          <label htmlFor="content" className="text-sm font-medium">
            Your response
          </label>
          <Textarea
            id="content"
            rows={10}
            {...register('content')}
            aria-invalid={!!errors.content}
          />
          <p className="text-xs text-muted-foreground">
            {wordCount(content ?? '')} word{wordCount(content ?? '') === 1 ? '' : 's'}
            {lesson.assignment.minimum_word_count !== null
              ? ` (minimum ${lesson.assignment.minimum_word_count})`
              : ''}
          </p>
          {errors.content && <p className="text-sm text-red-600">{errors.content.message}</p>}
        </div>
        {errors.root && <p className="text-sm text-red-600">{errors.root.message}</p>}
        <Button type="submit" className="w-full" disabled={isSubmitting}>
          {isSubmitting ? 'Submitting…' : 'Submit assignment'}
        </Button>
      </form>
    </div>
  )
}
