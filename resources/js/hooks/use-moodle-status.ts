import axios from "axios"
import { useEffect, useRef, useState } from "react"

export type MoodleStatus = "completed" | "in-progress" | "not-started" | "unknown" | "pending"

type MoodleStatuses = Record<number, MoodleStatus>

const POLL_INTERVAL_MS = 4000

function csrfToken(): string | null {
	return document.head.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? null
}

async function fetchStatusBatch(
	courseId: number,
	traineeIds: number[],
	signal: AbortSignal,
): Promise<MoodleStatuses> {
	const response = await axios.post(
		route("overview.get-moodle-status-batch"),
		{ course_id: courseId, trainee_ids: traineeIds },
		{ headers: { "X-CSRF-TOKEN": csrfToken() }, signal },
	)

	return response.data.success ? (response.data.statuses ?? {}) : {}
}

export function useMoodleStatus(
	trainees: Array<{ id: number; vatsimId: number }>,
	courseId: number,
) {
	const [statuses, setStatuses] = useState<MoodleStatuses>({})
	const [loading, setLoading] = useState(false)
	const pollTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null)

	const traineesKey = trainees.map((t) => t.id).join(",")

	useEffect(() => {
		if (trainees.length === 0) return

		const controller = new AbortController()
		setStatuses({})
		setLoading(true)

		fetchStatusBatch(courseId, trainees.map((t) => t.id), controller.signal)
			.then((result) => {
				if (!controller.signal.aborted) setStatuses(result)
			})
			.catch(() => {
				if (!controller.signal.aborted) {
					const fallback: MoodleStatuses = {}
					trainees.forEach((t) => (fallback[t.id] = "unknown"))
					setStatuses(fallback)
				}
			})
			.finally(() => {
				if (!controller.signal.aborted) setLoading(false)
			})

		return () => {
			controller.abort()
			if (pollTimerRef.current) clearTimeout(pollTimerRef.current)
		}
	}, [traineesKey, courseId])

	useEffect(() => {
		const pendingIds = Object.entries(statuses)
			.filter(([, status]) => status === "pending")
			.map(([id]) => Number(id))

		if (pendingIds.length === 0) return

		const controller = new AbortController()

		pollTimerRef.current = setTimeout(() => {
			fetchStatusBatch(courseId, pendingIds, controller.signal)
				.then((result) => {
					if (!controller.signal.aborted) {
						setStatuses((prev) => ({ ...prev, ...result }))
					}
				})
				.catch(() => {})
		}, POLL_INTERVAL_MS)

		return () => {
			controller.abort()
			if (pollTimerRef.current) clearTimeout(pollTimerRef.current)
		}
	}, [statuses, traineesKey, courseId])

	return { statuses, loading }
}
