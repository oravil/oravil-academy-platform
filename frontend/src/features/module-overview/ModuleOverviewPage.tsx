import { useQuery } from '@tanstack/react-query'
import { CheckCircle2, Circle, Lock, type LucideIcon } from 'lucide-react'
import { Link } from 'react-router-dom'
import { Button } from '@/components/ui/button'
import {
  getLearnerProgress,
  getModuleOverview,
  isApiError,
  type LearnerProgressResponse,
  type LessonStatus,
  type ModuleOverviewLessonResponse,
  type ModuleOverviewResponse,
} from '@/lib/api'
import { CURRENT_MODULE_ID } from './constants'

const STATUS_CONFIG: Record<LessonStatus, { icon: LucideIcon; className: string; label: string }> =
  {
    locked: { icon: Lock, className: 'text-muted-foreground', label: 'Locked' },
    available: { icon: Circle, className: 'text-primary', label: 'Available' },
    complete: { icon: CheckCircle2, className: 'text-green-600', label: 'Complete' },
  }

// Locked lessons are not navigable from the UI (frontend gating layer, OA-MVP-010 Step 5);
// available and complete lessons may be opened — a learner may reopen a completed lesson
// to re-read it, per MVP_WIREFRAMES.md Navigation Behavior.
function LessonRow({ lesson }: { lesson: ModuleOverviewLessonResponse }) {
  const { icon: Icon, className, label } = STATUS_CONFIG[lesson.status]
  const isNavigable = lesson.status === 'available' || lesson.status === 'complete'

  const content = (
    <>
      <Icon className={className} aria-hidden />
      <span className="flex-1">
        Lesson {lesson.position}: {lesson.title}
      </span>
      <span className={`text-sm font-medium ${className}`}>{label}</span>
    </>
  )

  if (isNavigable) {
    return (
      <li>
        <Link
          to={`/lessons/${lesson.lesson_id}`}
          className="flex items-center gap-3 rounded-md border p-3 hover:bg-accent"
        >
          {content}
        </Link>
      </li>
    )
  }

  return <li className="flex items-center gap-3 rounded-md border p-3">{content}</li>
}

// Primary action states per docs repo MVP_WIREFRAMES.md, Screen 1 — Module Overview.
function primaryActionLabel(
  overview: ModuleOverviewResponse,
  progress: LearnerProgressResponse
): string {
  if (progress.module_status === 'complete') {
    return 'Proceed to Module Complete'
  }

  if (progress.lessons_complete === 0) {
    return 'Begin Lesson 1'
  }

  const currentLesson = overview.lessons.find(
    (lesson) => lesson.lesson_id === progress.current_lesson_id
  )
  return currentLesson ? `Continue to Lesson ${currentLesson.position}` : 'Continue'
}

// The only lesson a learner can move forward into is the one lesson the domain
// rules mark 'available' (OA-MVP-005 Rules 3-4) — deriving from the lesson list
// rather than progress.current_lesson_id avoids depending on that field being non-null.
function nextLessonId(overview: ModuleOverviewResponse): string | null {
  return overview.lessons.find((lesson) => lesson.status === 'available')?.lesson_id ?? null
}

export function ModuleOverviewPage() {
  const overviewQuery = useQuery({
    queryKey: ['module-overview', CURRENT_MODULE_ID],
    queryFn: () => getModuleOverview(CURRENT_MODULE_ID),
    retry: false,
  })

  const progressQuery = useQuery({
    queryKey: ['learner-progress', CURRENT_MODULE_ID],
    queryFn: () => getLearnerProgress(CURRENT_MODULE_ID),
    retry: false,
  })

  if (overviewQuery.isLoading || progressQuery.isLoading) {
    return <div className="p-8">Loading…</div>
  }

  if (overviewQuery.isError || progressQuery.isError) {
    const error = overviewQuery.error ?? progressQuery.error
    const message = isApiError(error)
      ? error.message
      : 'Unable to load this module. Please try again.'
    return <div className="p-8 text-red-600">{message}</div>
  }

  const overview = overviewQuery.data
  const progress = progressQuery.data

  if (!overview || !progress) {
    return null
  }

  return (
    <div className="mx-auto max-w-2xl space-y-6 p-8">
      <div className="space-y-2">
        <h1 className="text-2xl font-semibold">{overview.title}</h1>
        <p className="text-muted-foreground">{overview.purpose}</p>
      </div>

      <div className="space-y-1 rounded-md border p-4">
        <h2 className="text-sm font-medium">Deliverable</h2>
        <p className="text-sm text-muted-foreground">{overview.deliverable_description}</p>
      </div>

      <ul className="space-y-2">
        {overview.lessons.map((lesson) => (
          <LessonRow key={lesson.lesson_id} lesson={lesson} />
        ))}
      </ul>

      <PrimaryAction overview={overview} progress={progress} />
    </div>
  )
}

// Module Complete has no screen yet (OA-MVP-010 Step 7) — that state stays disabled.
// Begin/Continue navigate to Lesson View now that VS-003 is live.
function PrimaryAction({
  overview,
  progress,
}: {
  overview: ModuleOverviewResponse
  progress: LearnerProgressResponse
}) {
  const label = primaryActionLabel(overview, progress)

  if (progress.module_status === 'complete') {
    return (
      <Button
        className="w-full"
        disabled
        title="Module Complete is not available yet."
      >
        {label}
      </Button>
    )
  }

  const targetLessonId = nextLessonId(overview)

  if (!targetLessonId) {
    return (
      <Button className="w-full" disabled>
        {label}
      </Button>
    )
  }

  return (
    <Button asChild className="w-full">
      <Link to={`/lessons/${targetLessonId}`}>{label}</Link>
    </Button>
  )
}
