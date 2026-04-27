import { router } from "@inertiajs/react"
import { useEffect, useState } from "react"
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
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from "@/components/ui/select"
import type { Mentor, Trainee } from "@/types/mentor"

interface ClaimConfirmDialogProps {
	trainee: Trainee | null
	courseId: number | null
	isOpen: boolean
	onClose: () => void
}

export function ClaimConfirmDialog({
	trainee,
	courseId,
	isOpen,
	onClose,
}: ClaimConfirmDialogProps) {
	const [isClaiming, setIsClaiming] = useState(false)

	const handleClaim = () => {
		if (!trainee || !courseId) return

		setIsClaiming(true)
		router.post(
			route("overview.claim-trainee"),
			{
				trainee_id: trainee.id,
				course_id: courseId,
			},
			{
				preserveScroll: true,
				onFinish: () => {
					setIsClaiming(false)
					onClose()
				},
			},
		)
	}

	return (
		<Dialog onOpenChange={onClose} open={isOpen}>
			<DialogContent>
				<DialogHeader>
					<DialogTitle>Claim Trainee</DialogTitle>
					<DialogDescription>
						{trainee?.claimedBy && trainee.claimedBy !== "You" ? (
							<>
								<span className="font-medium">{trainee.name}</span> is currently
								claimed by{" "}
								<span className="font-medium">{trainee.claimedBy}</span>.
							</>
						) : (
							<>
								Are you sure you want to claim{" "}
								<span className="font-medium">{trainee?.name}</span>?
							</>
						)}
					</DialogDescription>
				</DialogHeader>
				<DialogFooter>
					<Button disabled={isClaiming} onClick={onClose} variant="outline">
						Cancel
					</Button>
					<Button disabled={isClaiming} onClick={handleClaim}>
						{isClaiming ? "Claiming..." : "Claim Trainee"}
					</Button>
				</DialogFooter>
			</DialogContent>
		</Dialog>
	)
}

interface AssignDialogProps {
	trainee: Trainee | null
	courseId: number | null
	isOpen: boolean
	onClose: () => void
}

export function AssignDialog({
	trainee,
	courseId,
	isOpen,
	onClose,
}: AssignDialogProps) {
	const [mentors, setMentors] = useState<Mentor[]>([])
	const [selectedMentorId, setSelectedMentorId] = useState<string>("")
	const [isLoading, setIsLoading] = useState(false)
	const [isAssigning, setIsAssigning] = useState(false)

	useEffect(() => {
		if (isOpen && courseId) {
			setIsLoading(true)
			fetch(route("overview.get-course-mentors", courseId))
				.then((res) => res.json())
				.then((data) => {
					setMentors(data)
					setIsLoading(false)
				})
				.catch(() => {
					setIsLoading(false)
				})
		}
	}, [isOpen, courseId])

	const handleAssign = () => {
		if (!trainee || !courseId || !selectedMentorId) return

		setIsAssigning(true)
		router.post(
			route("overview.assign-trainee"),
			{
				trainee_id: trainee.id,
				course_id: courseId,
				mentor_id: parseInt(selectedMentorId),
			},
			{
				preserveScroll: true,
				onFinish: () => {
					setIsAssigning(false)
					setSelectedMentorId("")
					onClose()
				},
			},
		)
	}

	const handleClose = () => {
		setSelectedMentorId("")
		onClose()
	}

	return (
		<Dialog onOpenChange={handleClose} open={isOpen}>
			<DialogContent>
				<DialogHeader>
					<DialogTitle>Assign Trainee to Mentor</DialogTitle>
					<DialogDescription>
						Select a mentor to assign{" "}
						<span className="font-medium">{trainee?.name}</span> to.
					</DialogDescription>
				</DialogHeader>
				<div className="py-4">
					<Select
						disabled={isLoading}
						onValueChange={setSelectedMentorId}
						value={selectedMentorId}
					>
						<SelectTrigger>
							<SelectValue
								placeholder={
									isLoading ? "Loading mentors..." : "Select a mentor"
								}
							/>
						</SelectTrigger>
						<SelectContent>
							{mentors.map((mentor) => (
								<SelectItem key={mentor.id} value={mentor.id.toString()}>
									{mentor.name} ({mentor.vatsim_id})
								</SelectItem>
							))}
						</SelectContent>
					</Select>
				</div>
				<DialogFooter>
					<Button
						disabled={isAssigning}
						onClick={handleClose}
						variant="outline"
					>
						Cancel
					</Button>
					<Button
						disabled={!selectedMentorId || isAssigning}
						onClick={handleAssign}
					>
						{isAssigning ? "Assigning..." : "Assign"}
					</Button>
				</DialogFooter>
			</DialogContent>
		</Dialog>
	)
}
