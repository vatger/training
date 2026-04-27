import { Archive, Settings, Users } from "lucide-react"
import { useState } from "react"
import { Badge } from "@/components/ui/badge"
import { Button } from "@/components/ui/button"
import {
	Card,
	CardContent,
	CardDescription,
	CardFooter,
	CardHeader,
	CardTitle,
} from "@/components/ui/card"
import { Skeleton } from "@/components/ui/skeleton"
import {
	getCourseTypeDisplay,
	getPositionColor,
	getTypeColor,
} from "@/lib/course-utils"
import type { MentorCourse, Trainee } from "@/types/mentor"
import { AddTrainee } from "./add-trainee"
import { ManageMentorsModal } from "./manage-mentors-modal"
import { PastTraineesModal } from "./past-trainees-modal"
import { TraineeDataTable } from "./trainee-data-table"

interface CourseDetailProps {
	course: MentorCourse
	onRemarkClick: (trainee: Trainee) => void
	onClaimClick: (trainee: Trainee) => void
	onAssignClick: (trainee: Trainee) => void
	isLoading?: boolean
}

function CourseDetailSkeleton() {
	return (
		<Card className="gap-0">
			<CardHeader className="border-b">
				<div className="flex items-center justify-between">
					<div className="space-y-2">
						<Skeleton className="h-7 w-48" />
						<div className="flex items-center gap-2">
							<Skeleton className="h-5 w-16" />
							<Skeleton className="h-5 w-24" />
						</div>
					</div>
					<div className="flex gap-2">
						<Skeleton className="h-9 w-32" />
						<Skeleton className="h-9 w-36" />
					</div>
				</div>
			</CardHeader>

			<CardContent className="p-6">
				<div className="space-y-4">
					{[1, 2, 3].map((i) => (
						<div className="flex items-center gap-4" key={i}>
							<Skeleton className="h-10 w-10 rounded-full" />
							<div className="flex-1 space-y-2">
								<Skeleton className="h-4 w-32" />
								<Skeleton className="h-3 w-24" />
							</div>
							<Skeleton className="h-4 w-24" />
							<Skeleton className="h-4 w-20" />
							<Skeleton className="h-4 w-28" />
							<Skeleton className="h-9 w-24" />
						</div>
					))}
				</div>
			</CardContent>
		</Card>
	)
}

export function CourseDetail({
	course,
	onRemarkClick,
	onClaimClick,
	onAssignClick,
	isLoading,
}: CourseDetailProps) {
	const [isPastTraineesOpen, setIsPastTraineesOpen] = useState(false)
	const [isManageMentorsOpen, setIsManageMentorsOpen] = useState(false)

	if (isLoading) {
		return <CourseDetailSkeleton />
	}

	return (
		<>
			<Card className="gap-0">
				<CardHeader className="border-b">
					<div className="flex items-center justify-between">
						<div>
							<CardTitle className="text-xl">{course.name}</CardTitle>
							<CardDescription className="mt-1 flex items-center gap-2">
								<Badge
									className={getPositionColor(course.position)}
									variant="outline"
								>
									{course.position}
								</Badge>
								<Badge className={getTypeColor(course.type)} variant="outline">
									{getCourseTypeDisplay(course.type)}
								</Badge>
							</CardDescription>
						</div>
						<div className="flex gap-2">
							<Button
								onClick={() => setIsPastTraineesOpen(true)}
								size="sm"
								variant="outline"
							>
								<Archive className="mr-2 h-4 w-4" />
								Past Trainees
							</Button>
							<Button
								onClick={() => setIsManageMentorsOpen(true)}
								size="sm"
								variant="outline"
							>
								<Settings className="mr-2 h-4 w-4" />
								Manage Mentors
							</Button>
						</div>
					</div>
				</CardHeader>

				<CardContent className="p-0">
					{course.trainees.length > 0 ? (
						<TraineeDataTable
							course={course}
							onAssignClick={onAssignClick}
							onClaimClick={onClaimClick}
							onRemarkClick={onRemarkClick}
							trainees={course.trainees}
						/>
					) : (
						<div className="flex flex-col items-center justify-center py-12 text-center">
							<Users className="mb-4 h-12 w-12 text-muted-foreground" />
							<h3 className="mb-2 text-lg font-medium">No trainees yet</h3>
							<p className="mb-4 text-sm text-muted-foreground">
								Add a trainee to this course to get started
							</p>
							<AddTrainee courseId={course.id} />
						</div>
					)}
				</CardContent>

				{course.trainees.length > 0 && (
					<CardFooter className="border-t">
						<div className="flex w-full items-center justify-start gap-2">
							<AddTrainee courseId={course.id} />
						</div>
					</CardFooter>
				)}
			</Card>

			{/* Modals */}
			<PastTraineesModal
				course={course}
				isOpen={isPastTraineesOpen}
				onClose={() => setIsPastTraineesOpen(false)}
			/>
			<ManageMentorsModal
				course={course}
				isOpen={isManageMentorsOpen}
				onClose={() => setIsManageMentorsOpen(false)}
			/>
		</>
	)
}
