import { useQuery } from '@tanstack/react-query'
import { CheckCircle2 } from 'lucide-react'
import { Link, useParams } from 'react-router-dom'
import { Button } from '@/components/ui/button'
import { getModuleCompletion, isApiError } from '@/lib/api'

function BackToOverviewLink() {
  return (
    <Link to="/" className="text-sm text-primary hover:underline">
      ← Back to Module Overview
    </Link>
  )
}

export function ModuleCompletePage() {
  const { moduleId } = useParams<{ moduleId: string }>()

  const completionQuery = useQuery({
    queryKey: ['module-completion', moduleId],
    queryFn: () => getModuleCompletion(moduleId as string),
    retry: false,
    enabled: Boolean(moduleId),
  })

  if (completionQuery.isLoading) {
    return <div className="p-8">Loading…</div>
  }

  if (completionQuery.isError) {
    const error = completionQuery.error

    if (isApiError(error) && error.status === 403) {
      return (
        <div className="mx-auto max-w-2xl space-y-4 p-8">
          <BackToOverviewLink />
          <div className="space-y-2 rounded-md border p-6 text-center">
            <h1 className="text-lg font-semibold">Module not yet complete</h1>
            <p className="text-sm text-muted-foreground">{error.message}</p>
          </div>
        </div>
      )
    }

    const message = isApiError(error)
      ? error.message
      : 'Unable to load this module. Please try again.'

    return (
      <div className="mx-auto max-w-2xl space-y-4 p-8">
        <BackToOverviewLink />
        <p className="text-red-600">{message}</p>
      </div>
    )
  }

  const completion = completionQuery.data

  if (!completion) {
    return null
  }

  return (
    <div className="mx-auto max-w-2xl space-y-6 p-8">
      <BackToOverviewLink />

      <div className="space-y-2 text-center">
        <CheckCircle2 className="mx-auto text-green-600" size={40} aria-hidden />
        <h1 className="text-2xl font-semibold">Module Complete</h1>
        <p className="text-muted-foreground">{completion.title}</p>
      </div>

      <ul className="space-y-2">
        {completion.completed_lessons.map((lesson) => (
          <li key={lesson.lesson_id} className="flex items-center gap-3 rounded-md border p-3">
            <CheckCircle2 className="text-green-600" aria-hidden />
            <span className="flex-1">
              Lesson {lesson.position}: {lesson.title}
            </span>
            <span className="text-sm font-medium text-green-600">Complete</span>
          </li>
        ))}
      </ul>

      {completion.deliverable_description && (
        <div className="space-y-1 rounded-md border p-4">
          <h2 className="text-sm font-medium">Deliverable</h2>
          <p className="text-sm text-muted-foreground">{completion.deliverable_description}</p>
        </div>
      )}

      {completion.survey_submitted ? (
        <div className="flex items-center justify-center gap-2 rounded-md border p-4 text-center text-sm font-medium text-green-600">
          <CheckCircle2 aria-hidden />
          Post-module survey submitted — thank you for your feedback.
        </div>
      ) : (
        <>
          <p className="text-sm text-muted-foreground">
            Next, complete a short post-module survey to share your feedback.
          </p>

          <Button asChild className="w-full">
            <Link to={`/modules/${completion.module_id}/survey`}>
              Proceed to post-module survey
            </Link>
          </Button>
        </>
      )}
    </div>
  )
}
