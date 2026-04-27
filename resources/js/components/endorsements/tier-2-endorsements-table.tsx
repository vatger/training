import { router, usePage } from "@inertiajs/react"
import { Award, ExternalLink } from "lucide-react"
import { useEffect, useState } from "react"
import { toast } from "sonner"
import { getPositionIcon, getStatusBadge } from "@/pages/endorsements/trainee"
import type { Endorsement } from "@/types"
import { Button } from "../ui/button"
import {
	Table,
	TableBody,
	TableCell,
	TableHead,
	TableHeader,
	TableRow,
} from "../ui/table"

export default function Tier2EndorsementsTable({
	endorsements,
}: {
	endorsements: Endorsement[]
}) {
	const [requestingEndorsement, setRequestingEndorsement] = useState<
		number | null
	>(null)
	const { flash } = usePage().props as any

	useEffect(() => {
		if (flash?.success) {
			toast.success(flash.success)
		}
		if (flash?.error) {
			toast.error(flash.error)
		}
	}, [flash])

	const getMoodleUrl = (courseId: number) => {
		return `https://moodle.vatsim-germany.org/course/view.php?id=${courseId}`
	}

	const handleRequestEndorsement = (endorsementId: number) => {
		setRequestingEndorsement(endorsementId)

		router.post(
			`/endorsements/tier2/${endorsementId}/request`,
			{},
			{
				preserveState: false,
				preserveScroll: true,
				onFinish: () => {
					setRequestingEndorsement(null)
				},
			},
		)
	}

	return (
		<div className="rounded-md border">
			<Table>
				<TableHeader>
					<TableRow>
						<TableHead>Position</TableHead>
						<TableHead>Status</TableHead>
						<TableHead className="text-right">Actions</TableHead>
					</TableRow>
				</TableHeader>
				<TableBody>
					{endorsements.map((endorsement) => (
						<TableRow className="h-18" key={endorsement.position}>
							<TableCell>
								<div className="flex items-center gap-3">
									<div className="flex h-8 w-8 items-center justify-center rounded-full bg-purple-100 text-purple-600">
										{getPositionIcon(endorsement.type)}
									</div>
									<div>
										<div className="font-medium">{endorsement.position}</div>
										<div className="text-sm text-muted-foreground">
											{endorsement.fullName}
										</div>
									</div>
								</div>
							</TableCell>
							<TableCell>{getStatusBadge(endorsement.status)}</TableCell>
							<TableCell className="text-right">
								<div className="flex justify-end gap-2">
									{endorsement.moodleCourseId && (
										<Button asChild size="sm" variant="outline">
											<a
												className="flex items-center gap-2"
												href={getMoodleUrl(endorsement.moodleCourseId)}
												rel="noopener noreferrer"
												target="_blank"
											>
												View Course
												<ExternalLink className="h-3 w-3" />
											</a>
										</Button>
									)}
									{endorsement.moodleCompleted &&
										!endorsement.hasEndorsement && (
											<Button
												className="flex items-center gap-2"
												disabled={requestingEndorsement === endorsement.id}
												onClick={() =>
													handleRequestEndorsement(endorsement.id!)
												}
												size="sm"
												variant="default"
											>
												{requestingEndorsement === endorsement.id ? (
													"Requesting..."
												) : (
													<>
														Get Endorsement
														<Award className="h-3 w-3" />
													</>
												)}
											</Button>
										)}
								</div>
							</TableCell>
						</TableRow>
					))}
				</TableBody>
			</Table>
		</div>
	)
}
