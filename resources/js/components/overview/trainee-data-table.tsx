import { Link, router } from "@inertiajs/react"
import {
	type ColumnDef,
	flexRender,
	getCoreRowModel,
	useReactTable,
	type VisibilityState,
} from "@tanstack/react-table"
import {
	AlertCircle,
	Award,
	CheckCircle,
	CheckCircle2,
	ChevronDown,
	ChevronUp,
	Clock,
	Eye,
	FileText,
	Loader2,
	MoreVertical,
	Plus,
	UserCheck,
	UserMinus,
	UserPlus,
	Users,
} from "lucide-react"
import { useEffect, useState } from "react"
import { Badge } from "@/components/ui/badge"
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
	DropdownMenu,
	DropdownMenuContent,
	DropdownMenuItem,
	DropdownMenuSeparator,
	DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import {
	Table,
	TableBody,
	TableCell,
	TableHead,
	TableHeader,
	TableRow,
} from "@/components/ui/table"
import {
	Tooltip,
	TooltipContent,
	TooltipProvider,
	TooltipTrigger,
} from "@/components/ui/tooltip"
import { useMoodleStatus } from "@/hooks/use-moodle-status"
import type { MentorCourse, Trainee } from "@/types/mentor"
import { ProgressModal } from "./progress-modal"
import { SoloModal } from "./solo-modal"

interface TraineeDataTableProps {
	trainees: Trainee[]
	course: MentorCourse
	onRemarkClick: (trainee: Trainee) => void
	onClaimClick: (trainee: Trainee) => void
	onAssignClick: (trainee: Trainee) => void
}

function TraineeRowActions({
	trainee,
	courseId,
	rowIndex,
	totalRows,
	onClaimClick,
	onAssignClick,
	onMoveUp,
	onMoveDown,
}: {
	trainee: Trainee
	courseId: number
	rowIndex: number
	totalRows: number
	onRemarkClick: (trainee: Trainee) => void
	onClaimClick: (trainee: Trainee) => void
	onAssignClick: (trainee: Trainee) => void
	onMoveUp: () => void
	onMoveDown: () => void
}) {
	const [isRemoving, setIsRemoving] = useState(false)
	const [isFinishing, setIsFinishing] = useState(false)
	const [removeOpen, setRemoveOpen] = useState(false)
	const [finishOpen, setFinishOpen] = useState(false)
	const [dropdownOpen, setDropdownOpen] = useState(false)

	const isFirst = rowIndex === 0
	const isLast = rowIndex === totalRows - 1

	const handleRemoveTrainee = () => {
		setIsRemoving(true)
		router.post(
			route("overview.remove-trainee"),
			{
				trainee_id: trainee.id,
				course_id: courseId,
			},
			{
				onFinish: () => {
					setIsRemoving(false)
					setRemoveOpen(false)
				},
			},
		)
	}

	const handleFinishTrainee = () => {
		setIsFinishing(true)
		router.post(
			route("overview.finish-trainee"),
			{
				trainee_id: trainee.id,
				course_id: courseId,
			},
			{
				onFinish: () => setIsFinishing(false),
			},
		)
	}

	const handleMoveUp = (e: React.MouseEvent) => {
		e.preventDefault()
		onMoveUp()
	}

	const handleMoveDown = (e: React.MouseEvent) => {
		e.preventDefault()
		onMoveDown()
	}

	return (
		<>
			<div className="flex items-center justify-end gap-2">
				<Button onClick={() => setFinishOpen(true)} size="sm" variant="success">
					<CheckCircle2 className="mr-1 h-3 w-3" />
					Finish
				</Button>
				<DropdownMenu onOpenChange={setDropdownOpen} open={dropdownOpen}>
					<DropdownMenuTrigger asChild>
						<Button size="sm" variant="ghost">
							<MoreVertical className="h-4 w-4" />
						</Button>
					</DropdownMenuTrigger>
					<DropdownMenuContent align="end">
						<DropdownMenuItem asChild>
							<Link href={`/users/${trainee.vatsimId}`}>
								<FileText className="h-4 w-4" />
								View Profile
							</Link>
						</DropdownMenuItem>
						<DropdownMenuSeparator />
						{trainee.claimedBy !== "You" && (
							<DropdownMenuItem onClick={() => onClaimClick(trainee)}>
								<UserPlus className="h-4 w-4" />
								Claim Trainee
							</DropdownMenuItem>
						)}
						<DropdownMenuItem onClick={() => onAssignClick(trainee)}>
							<Users className="h-4 w-4" />
							Assign to Mentor
						</DropdownMenuItem>
						<DropdownMenuSeparator />
						<DropdownMenuItem
							disabled={isFirst}
							onSelect={(e) => {
								e.preventDefault()
								handleMoveUp(e as any)
							}}
						>
							<ChevronUp className="h-4 w-4" />
							Move Up
						</DropdownMenuItem>
						<DropdownMenuItem
							disabled={isLast}
							onSelect={(e) => {
								e.preventDefault()
								handleMoveDown(e as any)
							}}
						>
							<ChevronDown className="h-4 w-4" />
							Move Down
						</DropdownMenuItem>
						<DropdownMenuSeparator />
						<DropdownMenuItem
							className="text-destructive focus:text-destructive"
							onClick={() => setRemoveOpen(true)}
						>
							<UserMinus className="h-4 w-4 text-destructive" />
							Remove from Course
						</DropdownMenuItem>
					</DropdownMenuContent>
				</DropdownMenu>
			</div>
			<Dialog onOpenChange={setRemoveOpen} open={removeOpen}>
				<DialogContent className="gap-6">
					<DialogHeader>
						<DialogTitle>Remove Trainee</DialogTitle>
						<DialogDescription>
							Are you sure you want to remove{" "}
							<span className="font-medium">{trainee?.name}</span> from this
							course?
						</DialogDescription>
					</DialogHeader>
					<DialogFooter>
						<Button
							disabled={isRemoving}
							onClick={() => setRemoveOpen(false)}
							variant="outline"
						>
							Cancel
						</Button>
						<Button
							disabled={isRemoving}
							onClick={handleRemoveTrainee}
							variant="destructive"
						>
							{isRemoving ? "Removing..." : "Remove from Course"}
						</Button>
					</DialogFooter>
				</DialogContent>
			</Dialog>
			<Dialog onOpenChange={setFinishOpen} open={finishOpen}>
				<DialogContent className="gap-6">
					<DialogHeader>
						<DialogTitle>Complete Training</DialogTitle>
						<DialogDescription>
							Are you sure you want to give{" "}
							<span className="font-medium">{trainee?.name}</span> all of the
							endorsements for this course?
							<br />
						</DialogDescription>
					</DialogHeader>
					<DialogFooter>
						<Button
							disabled={isFinishing}
							onClick={() => setFinishOpen(false)}
							variant="outline"
						>
							Cancel
						</Button>
						<Button
							disabled={isFinishing}
							onClick={handleFinishTrainee}
							variant={"success"}
						>
							{isFinishing ? "Completing..." : "Complete Training"}
						</Button>
					</DialogFooter>
				</DialogContent>
			</Dialog>
		</>
	)
}

export function TraineeDataTable({
	trainees,
	course,
	onRemarkClick,
	onClaimClick,
	onAssignClick,
}: TraineeDataTableProps) {
	const [data, setData] = useState<Trainee[]>(trainees)
	const [isUnclaiming, setIsUnclaiming] = useState<number | null>(null)
	const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({})

	const [soloModalOpen, setSoloModalOpen] = useState(false)
	const [selectedTraineeForSolo, setSelectedTraineeForSolo] =
		useState<Trainee | null>(null)

	const [grantModalOpen, setGrantModalOpen] = useState(false)
	const [selectedTraineeForGrant, setSelectedTraineeForGrant] =
		useState<Trainee | null>(null)
	const [isGrantingEndorsement, setIsGrantingEndorsement] = useState(false)

	const [progressModalOpen, setProgressModalOpen] = useState(false)
	const [selectedTraineeForProgress, setSelectedTraineeForProgress] =
		useState<Trainee | null>(null)

	const traineeData = trainees.map((t) => ({ id: t.id, vatsimId: t.vatsimId }))
	const { statuses: moodleStatuses, loading: moodleLoading } = useMoodleStatus(
		traineeData,
		course.id,
	)

	useEffect(() => {
		setData(trainees)
	}, [trainees])

	useEffect(() => {
		const visibility: VisibilityState = {
			solo: course.type === "RTG" && course.position !== "GND",
			endorsement: course.type === "RTG" && course.position === "GND",
			moodleStatus:
				course.type === "GST" ||
				course.type === "EDMT" ||
				course.type === "RST",
		}
		setColumnVisibility(visibility)
	}, [course])

	const handleUnclaimTrainee = (trainee: Trainee) => {
		setIsUnclaiming(trainee.id)
		router.post(
			route("overview.unclaim-trainee"),
			{
				trainee_id: trainee.id,
				course_id: course.id,
			},
			{
				onFinish: () => setIsUnclaiming(null),
			},
		)
	}

	const handleGrantEndorsement = () => {
		if (!selectedTraineeForGrant) return

		setIsGrantingEndorsement(true)
		router.post(
			route("overview.grant-endorsement"),
			{
				trainee_id: selectedTraineeForGrant.id,
				course_id: course.id,
			},
			{
				preserveState: true,
				preserveScroll: true,
				only: ["courses"],
				onFinish: () => {
					setIsGrantingEndorsement(false)
					setGrantModalOpen(false)
					setSelectedTraineeForGrant(null)
				},
			},
		)
	}

	const formatRemarkDate = (dateString: string | null) => {
		if (!dateString) return ""
		const date = new Date(dateString)
		const now = new Date()
		const diffInDays = Math.floor(
			(now.getTime() - date.getTime()) / (1000 * 60 * 60 * 24),
		)

		if (diffInDays === 0) return "Today"
		if (diffInDays === 1) return "Yesterday"
		if (diffInDays < 7) return `${diffInDays} days ago`
		if (diffInDays < 30) return `${Math.floor(diffInDays / 7)} weeks ago`
		if (diffInDays < 365) return `${Math.floor(diffInDays / 30)} months ago`
		return date.toLocaleDateString("de")
	}

	const moveTrainee = (index: number, direction: "up" | "down") => {
		const newData = [...data]
		const newIndex = direction === "up" ? index - 1 : index + 1

		if (newIndex < 0 || newIndex >= newData.length) return

		;[newData[index], newData[newIndex]] = [newData[newIndex], newData[index]]

		setData(newData)

		const traineeIds = newData.map((t) => t.id)

		router.post(
			route("overview.update-trainee-order"),
			{
				course_id: course.id,
				trainee_ids: traineeIds,
			},
			{
				preserveScroll: true,
				preserveState: true,
				onError: (errors) => {
					console.error("Failed to update trainee order:", errors)
					setData(trainees)
				},
			},
		)
	}

	const columns: ColumnDef<Trainee>[] = [
		{
			id: "trainee",
			accessorKey: "name",
			header: "Trainee",
			cell: ({ row }) => {
				const trainee = row.original
				return (
					<div className="flex items-center gap-3">
						<div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 font-medium text-primary">
							{trainee.initials}
						</div>
						<div className="flex flex-col">
							<Link
								className="font-medium hover:underline"
								href={`/users/${trainee.vatsimId}`}
							>
								{trainee.name}
							</Link>
							<a
								className="text-sm text-muted-foreground hover:underline"
								href={`https://stats.vatsim.net/stats/${trainee.vatsimId}`}
								rel="noopener noreferrer"
								target="_blank"
							>
								{trainee.vatsimId}
							</a>
						</div>
					</div>
				)
			},
		},
		{
			id: "progress",
			accessorKey: "progress",
			header: "Progress",
			cell: ({ row }) => {
				const trainee = row.original
				return (
					<div className="space-y-1">
						{trainee.progress.length > 0 ? (
							<div className="flex items-center gap-1">
								{trainee.progress.slice(-5).map((passed) => (
									<div
										className={`h-2 w-2 rounded-full ${passed ? "bg-green-500" : "bg-red-500"}`}
										key={null}
										title={`Session was ${passed ? "Passed" : "Failed"}`}
									/>
								))}
								{trainee.progress.length > 5 && (
									<span className="ml-1 text-xs text-muted-foreground">
										+{trainee.progress.length - 5}
									</span>
								)}
								<Button
									className="ml-1 h-6 px-2"
									onClick={() => {
										setSelectedTraineeForProgress(trainee)
										setProgressModalOpen(true)
									}}
									size="sm"
									variant="ghost"
								>
									<Eye className="mr-1 h-3 w-3" />
									Details
								</Button>
								<Button
									className="size-6"
									onClick={() => {
										router.visit(
											route("training-logs.create", {
												traineeId: trainee.id,
												courseId: course.id,
												continue: true,
											}),
										)
									}}
									size="sm"
									variant="success"
								>
									<Plus className="h-3 w-3" />
								</Button>
							</div>
						) : (
							<div className="flex items-center gap-1">
								<span className="text-sm text-muted-foreground">
									No sessions yet
								</span>
								<Button
									className="size-6"
									onClick={() => {
										router.visit(
											route("training-logs.create", {
												traineeId: trainee.id,
												courseId: course.id,
											}),
										)
									}}
									size="icon"
									variant="outline"
								>
									<Plus className="h-3 w-3" />
								</Button>
							</div>
						)}
						{trainee.lastSession && (
							<div className="text-xs text-muted-foreground">
								Last: {new Date(trainee.lastSession).toLocaleDateString("de")}
							</div>
						)}
					</div>
				)
			},
		},
		{
			id: "solo",
			accessorKey: "soloStatus",
			header: "Solo",
			cell: ({ row }) => {
				const trainee = row.original
				return trainee.soloStatus ? (
					<Button
						className={
							trainee.soloStatus.remaining < 10
								? "border-red-200 bg-red-50 text-red-700 hover:bg-red-100 dark:border-red-800 dark:bg-red-950 dark:text-red-300 dark:hover:bg-red-900"
								: trainee.soloStatus.remaining < 20
									? "border-yellow-200 bg-yellow-50 text-yellow-700 hover:bg-yellow-100 dark:border-yellow-800 dark:bg-yellow-950 dark:text-yellow-300 dark:hover:bg-yellow-900"
									: "border-green-200 bg-green-50 text-green-700 hover:bg-green-100 dark:border-green-800 dark:bg-green-950 dark:text-green-300 dark:hover:bg-green-900"
						}
						onClick={() => {
							setSelectedTraineeForSolo(trainee)
							setSoloModalOpen(true)
						}}
						size="sm"
						variant="outline"
					>
						<Clock className="mr-1 h-3 w-3" />
						{trainee.soloStatus.remaining} days
					</Button>
				) : (
					<Button
						className="h-7 text-xs"
						onClick={() => {
							setSelectedTraineeForSolo(trainee)
							setSoloModalOpen(true)
						}}
						size="sm"
						variant="ghost"
					>
						<Plus className="mr-1 h-3 w-3" />
						Add Solo
					</Button>
				)
			},
		},
		{
			id: "endorsement",
			accessorKey: "endorsementStatus",
			header: "Endorsement",
			cell: ({ row }) => {
				const trainee = row.original
				const endorsementStatus = trainee.endorsementStatus

				return endorsementStatus ? (
					<Badge
						className="border-green-200 bg-green-50 text-green-700 dark:border-green-700 dark:bg-green-900 dark:text-green-300"
						variant="outline"
					>
						<Award className="mr-1 h-3 w-3" />
						{endorsementStatus}
					</Badge>
				) : (
					<Button
						className="h-7 text-xs"
						onClick={() => {
							setSelectedTraineeForGrant(trainee)
							setGrantModalOpen(true)
						}}
						size="sm"
						variant="ghost"
					>
						<CheckCircle2 className="h-3 w-3" />
						Grant Endorsement
					</Button>
				)
			},
		},
		{
			id: "moodleStatus",
			accessorKey: "moodleStatus",
			header: "Moodle Status",
			cell: ({ row }) => {
				const trainee = row.original
				const cacheKey = `${trainee.vatsimId}_${course.id}`
				const moodleStatus = moodleStatuses[cacheKey]

				if (moodleLoading && !moodleStatus) {
					return (
						<Badge
							className="border-gray-200 bg-gray-50 text-gray-700"
							variant="outline"
						>
							<Loader2 className="mr-1 h-3 w-3 animate-spin" />
							Loading...
						</Badge>
					)
				}

				if (!moodleStatus) {
					return <span className="text-sm text-muted-foreground">—</span>
				}

				const getStatusConfig = (status: string) => {
					switch (status) {
						case "completed":
							return {
								className:
									"border-green-200 bg-green-50 text-green-700 dark:border-green-700 dark:bg-green-900 dark:text-green-300",
								label: "Completed",
								icon: <CheckCircle className="mr-1 h-3 w-3" />,
							}
						case "in-progress":
							return {
								className:
									"border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-700 dark:bg-blue-900 dark:text-blue-300",
								label: "In Progress",
								icon: <Clock className="mr-1 h-3 w-3" />,
							}
						case "not-started":
							return {
								className:
									"border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300",
								label: "Not Started",
								icon: <AlertCircle className="mr-1 h-3 w-3" />,
							}
						default:
							return {
								className:
									"border-yellow-200 bg-yellow-50 text-yellow-700 dark:border-yellow-700 dark:bg-yellow-900 dark:text-yellow-300",
								label: "Unknown",
								icon: <AlertCircle className="mr-1 h-3 w-3" />,
							}
					}
				}

				const config = getStatusConfig(moodleStatus)

				return (
					<Badge className={config.className} variant="outline">
						{config.icon}
						{config.label}
					</Badge>
				)
			},
		},
		{
			id: "nextStep",
			accessorKey: "nextStep",
			header: "Next Step",
			cell: ({ row }) => {
				const trainee = row.original
				return (
					<div className="max-w-xs truncate text-sm">
						{trainee.nextStep || "—"}
					</div>
				)
			},
		},
		{
			id: "remark",
			accessorKey: "remark",
			header: "Remark",
			cell: ({ row }) => {
				const trainee = row.original
				return (
					<TooltipProvider>
						<Tooltip delayDuration={500}>
							<TooltipTrigger asChild>
								<button
									className="max-w-76 rounded p-1 text-left transition-colors hover:bg-muted/64"
									onClick={() => onRemarkClick(trainee)}
									type="button"
								>
									{trainee.remark?.text ? (
										<div>
											<div className="line-clamp-2 text-sm">
												{trainee.remark.text}
											</div>
											<div className="mt-1 text-xs text-muted-foreground">
												Click to edit
											</div>
										</div>
									) : (
										<div className="text-sm text-muted-foreground">
											Click to add remark
										</div>
									)}
								</button>
							</TooltipTrigger>
							{trainee.remark?.text && trainee.remark.updated_at && (
								<TooltipContent className="max-w-xs" side="top">
									<div className="space-y-1">
										<div className="font-medium">Last updated</div>
										<div className="text-sm">
											{formatRemarkDate(trainee.remark.updated_at)}
											{trainee.remark.author_name && (
												<>
													{" by "}
													<span className="font-medium">
														{trainee.remark.author_name}
													</span>
												</>
											)}
										</div>
									</div>
								</TooltipContent>
							)}
						</Tooltip>
					</TooltipProvider>
				)
			},
		},
		{
			id: "status",
			accessorKey: "claimedBy",
			header: "Status",
			cell: ({ row }) => {
				const trainee = row.original
				const isClaiming = isUnclaiming === trainee.id

				return trainee.claimedBy ? (
					<TooltipProvider>
						<Tooltip delayDuration={200}>
							<TooltipTrigger asChild>
								<Badge
									className={
										trainee.claimedBy === "You"
											? "cursor-pointer border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100"
											: "border-gray-200 bg-gray-50 text-gray-700"
									}
									onClick={
										trainee.claimedBy === "You" && !isClaiming
											? () => handleUnclaimTrainee(trainee)
											: undefined
									}
									variant="outline"
								>
									{trainee.claimedBy === "You" ? (
										<>
											<UserCheck className="mr-1 h-3 w-3" />
											{isClaiming ? "Unclaiming..." : "Claimed by you"}
										</>
									) : (
										<>
											<Users className="mr-1 h-3 w-3" />
											Claimed by {trainee.claimedBy}
										</>
									)}
								</Badge>
							</TooltipTrigger>
							{trainee.claimedBy === "You" && (
								<TooltipContent>
									<p>Click to unclaim trainee</p>
								</TooltipContent>
							)}
						</Tooltip>
					</TooltipProvider>
				) : (
					<Button
						onClick={() => onClaimClick(trainee)}
						size="sm"
						variant="outline"
					>
						<UserPlus className="mr-1 h-3 w-3" />
						Claim
					</Button>
				)
			},
		},
		{
			id: "actions",
			header: () => <div className="text-right">Actions</div>,
			cell: ({ row }) => {
				const trainee = row.original
				const index = row.index
				return (
					<TraineeRowActions
						courseId={course.id}
						onAssignClick={onAssignClick}
						onClaimClick={onClaimClick}
						onMoveDown={() => moveTrainee(index, "down")}
						onMoveUp={() => moveTrainee(index, "up")}
						onRemarkClick={onRemarkClick}
						rowIndex={index}
						totalRows={data.length}
						trainee={trainee}
					/>
				)
			},
		},
	]

	const table = useReactTable({
		data,
		columns,
		getCoreRowModel: getCoreRowModel(),
		state: {
			columnVisibility,
		},
		onColumnVisibilityChange: setColumnVisibility,
	})

	return (
		<div className="overflow-x-auto">
			<Table>
				<TableHeader>
					{table.getHeaderGroups().map((headerGroup) => (
						<TableRow key={headerGroup.id}>
							{headerGroup.headers.map((header) => (
								<TableHead
									className={header.id === "trainee" ? "pl-6" : ""}
									key={header.id}
								>
									{header.isPlaceholder
										? null
										: flexRender(
												header.column.columnDef.header,
												header.getContext(),
											)}
								</TableHead>
							))}
						</TableRow>
					))}
				</TableHeader>
				<TableBody>
					{table.getRowModel().rows.length > 0 ? (
						table.getRowModel().rows.map((row) => (
							<TableRow key={row.id}>
								{row.getVisibleCells().map((cell) => (
									<TableCell
										className={cell.column.id === "trainee" ? "pl-6" : ""}
										key={cell.id}
									>
										{flexRender(cell.column.columnDef.cell, cell.getContext())}
									</TableCell>
								))}
							</TableRow>
						))
					) : (
						<TableRow>
							<TableCell className="h-24 text-center" colSpan={columns.length}>
								No trainees found.
							</TableCell>
						</TableRow>
					)}
				</TableBody>
			</Table>
			<Dialog onOpenChange={setGrantModalOpen} open={grantModalOpen}>
				<DialogContent className="gap-6">
					<DialogHeader>
						<DialogTitle>Grant Endorsement</DialogTitle>
						<DialogDescription>
							Are you sure you want to grant{" "}
							<span className="font-medium">
								{selectedTraineeForGrant?.name}
							</span>{" "}
							the <span className="font-monospace">{course.soloStation}</span>{" "}
							endorsement?
						</DialogDescription>
					</DialogHeader>
					<DialogFooter>
						<Button
							disabled={isGrantingEndorsement}
							onClick={() => {
								setGrantModalOpen(false)
								setSelectedTraineeForGrant(null)
							}}
							variant={"outline"}
						>
							Cancel
						</Button>
						<Button
							disabled={isGrantingEndorsement}
							onClick={handleGrantEndorsement}
							variant="success"
						>
							{isGrantingEndorsement ? "Granting..." : "Grant Endorsement"}
						</Button>
					</DialogFooter>
				</DialogContent>
			</Dialog>
			<SoloModal
				courseId={course.id}
				isOpen={soloModalOpen}
				onClose={() => {
					setSoloModalOpen(false)
					setSelectedTraineeForSolo(null)
				}}
				trainee={selectedTraineeForSolo}
			/>
			<ProgressModal
				courseId={course.id}
				isOpen={progressModalOpen}
				onClose={() => {
					setProgressModalOpen(false)
					setSelectedTraineeForProgress(null)
				}}
				trainee={selectedTraineeForProgress}
			/>
		</div>
	)
}
