import { router } from "@inertiajs/react"
import { AlertCircle, CheckCircle, Clock, Loader2, X } from "lucide-react"
import { useState } from "react"
import { toast } from "sonner"
import { Button } from "@/components/ui/button"
import {
	Dialog,
	DialogContent,
	DialogDescription,
	DialogFooter,
	DialogHeader,
	DialogTitle,
} from "@/components/ui/dialog"
import {
	Tooltip,
	TooltipContent,
	TooltipProvider,
	TooltipTrigger,
} from "@/components/ui/tooltip"
import type { Course } from "@/pages/training/courses"

interface WaitingListButtonProps {
	course: Course
	onCourseUpdate?: (courseId: number, updates: Partial<Course>) => void
	variant?: "default" | "compact"
	className?: string
	size?: "sm" | "default" | "lg"
	userHasActiveRtgCourse?: boolean
	userHasActiveFamEdmtCourse?: boolean
	rtgRatingPending?: boolean
}

export default function WaitingListButton({
	course,
	onCourseUpdate,
	variant = "default",
	className = "",
	size = "sm",
	userHasActiveRtgCourse = false,
	userHasActiveFamEdmtCourse = false,
	rtgRatingPending = false,
}: WaitingListButtonProps) {
	const [isLoading, setIsLoading] = useState(false)
	const [loadingAction, setLoadingAction] = useState<
		"joining" | "leaving" | null
	>(null)
	const [showLeaveConfirmation, setShowLeaveConfirmation] = useState(false)
	const [isConfirmingInterest, setIsConfirmingInterest] = useState(false)

	const isFamEdmtType = course.type === "EDMT" || course.type === "FAM"

	const handleJoinWaitingList = async () => {
		if (isLoading || !course.can_join) return

		setIsLoading(true)
		setLoadingAction("joining")

		const optimisticUpdates: Partial<Course> = {
			is_on_waiting_list: true,
			waiting_list_joined_at: undefined,
			waiting_list_activity: undefined,
		}

		onCourseUpdate?.(course.id, optimisticUpdates)

		try {
			await new Promise<void>((resolve, reject) => {
				router.post(
					`/courses/${course.id}/waiting-list`,
					{},
					{
						preserveState: true,
						preserveScroll: true,
						onSuccess: () => {
							toast.success("Successfully joined waiting list!")
							router.reload({ only: ["courses"] })
							resolve()
						},
						onError: (errors) => {
							onCourseUpdate?.(course.id, {
								is_on_waiting_list: false,
								waiting_list_joined_at: undefined,
								waiting_list_activity: undefined,
							})

							const errorMessage =
								Object.values(errors).flat()[0] || "An error occurred"
							toast.error(
								typeof errorMessage === "string"
									? errorMessage
									: "Failed to join waiting list",
							)

							reject(new Error("Inertia request failed"))
						},
					},
				)
			})
		} catch (error) {
			console.error("Error joining waiting list:", error)
			toast.error("Connection error")

			onCourseUpdate?.(course.id, {
				is_on_waiting_list: false,
				waiting_list_joined_at: undefined,
				waiting_list_activity: undefined,
			})
		} finally {
			setIsLoading(false)
			setLoadingAction(null)
		}
	}

	const handleLeaveWaitingList = async () => {
		if (isLoading || !course.is_on_waiting_list) return

		setShowLeaveConfirmation(false)
		setIsLoading(true)
		setLoadingAction("leaving")

		const originalJoinedAt = course.waiting_list_joined_at
		const originalActivity = course.waiting_list_activity

		const optimisticUpdates: Partial<Course> = {
			is_on_waiting_list: false,
			waiting_list_joined_at: undefined,
			waiting_list_activity: undefined,
		}

		onCourseUpdate?.(course.id, optimisticUpdates)

		try {
			await new Promise<void>((resolve, reject) => {
				router.post(
					`/courses/${course.id}/waiting-list`,
					{},
					{
						preserveState: true,
						preserveScroll: true,
						onSuccess: () => {
							toast.success("Successfully left waiting list!")
							resolve()
						},
						onError: (errors) => {
							onCourseUpdate?.(course.id, {
								is_on_waiting_list: true,
								waiting_list_joined_at: originalJoinedAt,
								waiting_list_activity: originalActivity,
							})

							const errorMessage =
								Object.values(errors).flat()[0] || "An error occurred"
							toast.error(
								typeof errorMessage === "string"
									? errorMessage
									: "Failed to leave waiting list",
							)

							reject(new Error("Inertia request failed"))
						},
					},
				)
			})
		} catch (error) {
			console.error("Error leaving waiting list:", error)
			toast.error("Connection error")
		} finally {
			setIsLoading(false)
			setLoadingAction(null)
		}
	}

	const handleConfirmInterest = async () => {
		if (isConfirmingInterest) return

		setIsConfirmingInterest(true)

		onCourseUpdate?.(course.id, { waiting_list_interest_confirmed: true })

		try {
			await new Promise<void>((resolve, reject) => {
				router.post(
					`/courses/${course.id}/waiting-list/confirm-interest`,
					{},
					{
						preserveState: true,
						preserveScroll: true,
						onSuccess: () => {
							toast.success("Thanks for confirming your interest!")
							resolve()
						},
						onError: (errors) => {
							onCourseUpdate?.(course.id, {
								waiting_list_interest_confirmed: false,
							})

							const errorMessage =
								Object.values(errors).flat()[0] || "An error occurred"
							toast.error(
								typeof errorMessage === "string"
									? errorMessage
									: "Failed to confirm interest",
							)

							reject(new Error("Inertia request failed"))
						},
					},
				)
			})
		} catch (error) {
			console.error("Error confirming waiting list interest:", error)
			toast.error("Connection error")

			onCourseUpdate?.(course.id, { waiting_list_interest_confirmed: false })
		} finally {
			setIsConfirmingInterest(false)
		}
	}

	const handleButtonClick = () => {
		if (
			course.type === "RTG" &&
			userHasActiveRtgCourse &&
			!course.is_on_waiting_list
		) {
			toast.error("You can only join one rating course at a time")
			return
		}

		if (
			isFamEdmtType &&
			userHasActiveFamEdmtCourse &&
			!course.is_on_waiting_list
		) {
			toast.error(
				"You can only join one endorsement or familiarisation course at a time",
			)
			return
		}

		if (course.is_on_waiting_list) {
			setShowLeaveConfirmation(true)
		} else if (course.can_join) {
			handleJoinWaitingList()
		}
	}

	const getButtonContent = () => {
		if (isLoading) {
			return (
				<>
					<Loader2 className="h-4 w-4 animate-spin" />
					{variant === "compact"
						? ""
						: loadingAction === "leaving"
							? "Leaving..."
							: "Joining..."}
				</>
			)
		}

		if (course.is_on_waiting_list) {
			return (
				<>
					{variant === "compact" ? (
						<X className="h-4 w-4" />
					) : (
						<X className="h-4 w-4" />
					)}
					{variant === "compact" ? "" : "Leave Queue"}
				</>
			)
		}

		return (
			<>
				{variant === "compact" ? (
					<CheckCircle className="h-4 w-4" />
				) : (
					<Clock className="h-4 w-4" />
				)}
				{variant === "compact" ? "" : "Join Queue"}
			</>
		)
	}

	const isDisabledDueToRtgRestriction =
		course.type === "RTG" &&
		userHasActiveRtgCourse &&
		!course.is_on_waiting_list
	const isDisabledDueToFamEdmtRestriction =
		isFamEdmtType && userHasActiveFamEdmtCourse && !course.is_on_waiting_list
	const isDisabledDueToRtgRatingPending =
		course.type === "RTG" && rtgRatingPending && !course.is_on_waiting_list
	const isButtonDisabled =
		isLoading ||
		(!course.can_join && !course.is_on_waiting_list) ||
		isDisabledDueToRtgRestriction ||
		isDisabledDueToFamEdmtRestriction ||
		isDisabledDueToRtgRatingPending

	const getTooltipError = () => {
		if (isDisabledDueToRtgRatingPending) {
			return "Your rating upgrade is pending in our system. You'll be able to join once your new rating has been applied."
		}
		if (isDisabledDueToRtgRestriction) {
			return "You can only join one rating course at a time"
		}
		if (isDisabledDueToFamEdmtRestriction) {
			return "You can only join one endorsement or familiarisation course at a time"
		}
		return course.join_error || "Cannot join this course at the moment"
	}

	const button = (
		<Button
			className={className}
			disabled={isButtonDisabled}
			onClick={handleButtonClick}
			size={size}
			variant={course.is_on_waiting_list ? "destructive" : "default"}
		>
			{getButtonContent()}
		</Button>
	)

	if (isButtonDisabled && !course.is_on_waiting_list) {
		return (
			<>
				<TooltipProvider>
					<Tooltip>
						<TooltipTrigger asChild>
							<div className={variant === "compact" ? "" : "w-full"}>
								{button}
							</div>
						</TooltipTrigger>
						<TooltipContent className="max-w-xs" side="top">
							<div className="flex items-center gap-2">
								<AlertCircle className="h-4 w-4" />
								<span>{getTooltipError()}</span>
							</div>
						</TooltipContent>
					</Tooltip>
				</TooltipProvider>

				<Dialog
					onOpenChange={setShowLeaveConfirmation}
					open={showLeaveConfirmation}
				>
					<DialogContent>
						<DialogHeader>
							<DialogTitle>Leave Waiting List</DialogTitle>
							<DialogDescription>
								Are you sure you want to leave the waiting list for{" "}
								<strong>{course.trainee_display_name || course.name}</strong>?
								{course.waiting_list_joined_at && (
									<span className="mt-2 block text-sm">
										You will lose your place on the waiting list.
									</span>
								)}
							</DialogDescription>
						</DialogHeader>
						<DialogFooter>
							<Button
								onClick={() => setShowLeaveConfirmation(false)}
								variant="outline"
							>
								Cancel
							</Button>
							<Button onClick={handleLeaveWaitingList} variant="destructive">
								Leave Queue
							</Button>
						</DialogFooter>
					</DialogContent>
				</Dialog>
			</>
		)
	}

	return (
		<>
			<div className={variant === "compact" ? "" : "w-full space-y-2"}>
				{button}
				{course.is_on_waiting_list &&
					course.waiting_list_interest_confirmed === false && (
						<Button
							className="w-full"
							disabled={isConfirmingInterest}
							onClick={handleConfirmInterest}
							size={size}
							variant="outline"
						>
							{isConfirmingInterest ? (
								<Loader2 className="h-4 w-4 animate-spin" />
							) : (
								<CheckCircle className="h-4 w-4" />
							)}
							{variant === "compact" ? "" : "Confirm you're still interested"}
						</Button>
					)}
			</div>

			<Dialog
				onOpenChange={setShowLeaveConfirmation}
				open={showLeaveConfirmation}
			>
				<DialogContent>
					<DialogHeader>
						<DialogTitle>Leave Waiting List</DialogTitle>
						<DialogDescription>
							Are you sure you want to leave the waiting list for{" "}
							<strong>{course.trainee_display_name || course.name}</strong>?
							{course.waiting_list_joined_at && (
								<span className="mt-2 block text-sm">
									You will lose your place on the waiting list.
								</span>
							)}
						</DialogDescription>
					</DialogHeader>
					<DialogFooter>
						<Button
							onClick={() => setShowLeaveConfirmation(false)}
							variant="outline"
						>
							Cancel
						</Button>
						<Button onClick={handleLeaveWaitingList} variant="destructive">
							Leave Queue
						</Button>
					</DialogFooter>
				</DialogContent>
			</Dialog>
		</>
	)
}
