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
import { Textarea } from "@/components/ui/textarea"
import type { Trainee } from "@/types/mentor"

interface RemarkDialogProps {
	trainee: Trainee | null
	courseId: number | null
	isOpen: boolean
	onClose: () => void
}

export function RemarkDialog({
	trainee,
	courseId,
	isOpen,
	onClose,
}: RemarkDialogProps) {
	const [remarkText, setRemarkText] = useState("")
	const [isSaving, setIsSaving] = useState(false)

	// Update remarkText when trainee changes
	useEffect(() => {
		if (trainee) {
			setRemarkText(trainee.remark?.text || "")
		}
	}, [trainee])

	const handleSave = () => {
		if (!trainee || !courseId) return

		setIsSaving(true)
		router.post(
			route("overview.update-remark"),
			{
				trainee_id: trainee.id,
				course_id: courseId,
				remark: remarkText,
			},
			{
				preserveScroll: true,
				onFinish: () => {
					setIsSaving(false)
					onClose()
				},
			},
		)
	}

	const handleClose = () => {
		setRemarkText(trainee?.remark?.text || "")
		onClose()
	}

	return (
		<Dialog onOpenChange={handleClose} open={isOpen}>
			<DialogContent>
				<DialogHeader>
					<DialogTitle>Update Remark - {trainee?.name}</DialogTitle>
					<DialogDescription>
						Add notes about this trainee's availability, performance, or other
						relevant information.
					</DialogDescription>
				</DialogHeader>
				<Textarea
					maxLength={1000}
					onChange={(e) => setRemarkText(e.target.value)}
					placeholder="Enter remarks about this trainee..."
					rows={4}
					value={remarkText}
				/>
				<div className="text-right text-sm text-muted-foreground">
					{remarkText.length}/1000
				</div>
				<DialogFooter>
					<Button disabled={isSaving} onClick={handleClose} variant="outline">
						Cancel
					</Button>
					<Button disabled={isSaving} onClick={handleSave}>
						{isSaving ? "Saving..." : "Save"}
					</Button>
				</DialogFooter>
			</DialogContent>
		</Dialog>
	)
}
