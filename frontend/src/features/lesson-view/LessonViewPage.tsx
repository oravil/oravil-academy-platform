import { useQuery } from '@tanstack/react-query'
import ReactMarkdown, { type Components } from 'react-markdown'
import remarkGfm from 'remark-gfm'
import { Link, useParams } from 'react-router-dom'
import { Button } from '@/components/ui/button'
import { getLesson, isApiError } from '@/lib/api'

const markdownComponents: Components = {
  // Every seeded lesson opens with a level-1 heading restating the lesson title,
  // which the page already renders as its own <h1> above the content — suppressed
  // here to avoid showing the title twice.
  h1: () => null,
  h2: ({ children }) => <h2 className="mt-8 text-xl font-semibold first:mt-0">{children}</h2>,
  h3: ({ children }) => <h3 className="mt-6 text-lg font-medium">{children}</h3>,
  p: ({ children }) => <p className="mt-4 leading-relaxed first:mt-0">{children}</p>,
  ul: ({ children }) => <ul className="mt-4 list-disc space-y-1 pl-6">{children}</ul>,
  ol: ({ children }) => <ol className="mt-4 list-decimal space-y-1 pl-6">{children}</ol>,
  hr: () => <hr className="my-8 border-t" />,
  strong: ({ children }) => <strong className="font-semibold">{children}</strong>,
  blockquote: ({ children }) => (
    <blockquote className="mt-4 border-l-2 pl-4 italic text-muted-foreground">
      {children}
    </blockquote>
  ),
  table: ({ children }) => (
    <div className="mt-4 overflow-x-auto">
      <table className="w-full border-collapse text-sm">{children}</table>
    </div>
  ),
  th: ({ children }) => (
    <th className="border p-2 text-left font-medium">{children}</th>
  ),
  td: ({ children }) => <td className="border p-2 align-top">{children}</td>,
}

function BackToOverviewLink() {
  return (
    <Link to="/" className="text-sm text-primary hover:underline">
      ← Back to Module Overview
    </Link>
  )
}

export function LessonViewPage() {
  const { lessonId } = useParams<{ lessonId: string }>()

  const lessonQuery = useQuery({
    queryKey: ['lesson', lessonId],
    queryFn: () => getLesson(lessonId as string),
    retry: false,
    enabled: Boolean(lessonId),
  })

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
      : 'Unable to load this lesson. Please try again.'

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

  return (
    <div className="mx-auto max-w-2xl space-y-6 p-8">
      <BackToOverviewLink />

      <div className="space-y-2">
        <p className="text-sm font-medium text-muted-foreground">Lesson {lesson.position}</p>
        <h1 className="text-2xl font-semibold">{lesson.title}</h1>
        {lesson.estimated_reading_minutes !== null && (
          <p className="text-sm text-muted-foreground">
            {lesson.estimated_reading_minutes} min read
          </p>
        )}
      </div>

      <div>
        <ReactMarkdown remarkPlugins={[remarkGfm]} components={markdownComponents}>
          {lesson.content}
        </ReactMarkdown>
      </div>

      <div className="space-y-3 rounded-md border p-4">
        <h2 className="text-sm font-medium">Assignment: {lesson.assignment.deliverable_name}</h2>
        <p className="text-sm text-muted-foreground">{lesson.assignment.prompt}</p>
        <Button
          className="w-full"
          disabled
          title="Assignment submission is not available yet (VS-004)."
        >
          Proceed to Assignment Submission
        </Button>
      </div>
    </div>
  )
}
