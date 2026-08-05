import { Head, usePage } from "@inertiajs/react"
import {
	AlertCircle,
	CheckCircle,
	Radio,
	Shield,
	TowerControl,
	XCircle,
} from "lucide-react"
import ActiveSoloEndorsements from "@/components/endorsements/active-solo-endorsements"
import SoloEndorsementsTable from "@/components/endorsements/solo-endorsements-table"
import Tier1EndorsementsTable from "@/components/endorsements/tier-1-endorsements-table"
import Tier2EndorsementsTable from "@/components/endorsements/tier-2-endorsements-table"
import { Badge } from "@/components/ui/badge"
import {
	Card,
	CardContent,
	CardDescription,
	CardHeader,
	CardTitle,
} from "@/components/ui/card"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import AppLayout from "@/layouts/app-layout"
import { dashboard } from "@/routes"
import type { BreadcrumbItem, Endorsement } from "@/types"

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: "Dashboard",
		href: dashboard().url,
	},
	{
		title: "My Endorsements",
		href: route("endorsements.trainee"),
	},
]

export function getStatusBadge(status: string) {
	switch (status) {
		case "active":
			return (
				<Badge
					className="border-success-200 bg-success-50 text-success-700 dark:border-success-700 dark:bg-success-900 dark:text-success-300"
					variant="outline"
				>
					<CheckCircle className="mr-1 h-3 w-3" />
					Active
				</Badge>
			)
		case "warning":
			return (
				<Badge
					className="border-warning-200 bg-warning-50 text-warning-800 dark:border-warning-700 dark:bg-warning-900 dark:text-warning-300"
					variant="outline"
				>
					<AlertCircle className="mr-1 h-3 w-3" />
					Low Activity
				</Badge>
			)
		case "removal":
			return (
				<Badge
					className="border-danger-200 bg-danger-50 text-danger-700 dark:border-danger-700 dark:bg-danger-900 dark:text-danger-300"
					variant="outline"
				>
					<XCircle className="mr-1 h-3 w-3" />
					In Removal
				</Badge>
			)
		case "completed":
			return (
				<Badge
					className="border-success-200 bg-success-50 text-success-700 dark:border-success-700 dark:bg-success-900 dark:text-success-300"
					variant="outline"
				>
					<CheckCircle className="mr-1 h-3 w-3" />
					Moodle Completed
				</Badge>
			)
		case "available":
			return (
				<Badge
					className="border-primary-200 bg-primary-50 text-primary-700 dark:border-primary-700 dark:bg-primary-900 dark:text-primary-300"
					variant="outline"
				>
					Available
				</Badge>
			)
		default:
			return <Badge variant="outline">{status}</Badge>
	}
}

export function getPositionIcon(type: string) {
	switch (type) {
		case "GNDDEL":
			return <Radio className="h-4 w-4" />
		case "TWR":
			return <TowerControl className="h-4 w-4" />
		case "APP":
			return <Shield className="h-4 w-4" />
		case "CTR":
			return <Shield className="h-4 w-4" />
		default:
			return <Radio className="h-4 w-4" />
	}
}

export default function EndorsementsDashboard() {
	const { tier1Endorsements, tier2Endorsements, soloEndorsements } = usePage<{
		tier1Endorsements: Endorsement[]
		tier2Endorsements: Endorsement[]
		soloEndorsements: Endorsement[]
		isVatsimUser: boolean
	}>().props

	const activeSolos = soloEndorsements.filter((e) => e.status === "active")
	const hasRemovalEndorsements = tier1Endorsements.some((e) => e.removalDate)

	return (
		<AppLayout breadcrumbs={breadcrumbs}>
			<Head title="Endorsements" />
			<div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
				{/* Removal Warning Banner */}
				{hasRemovalEndorsements && (
					<Card className="border-danger-200 bg-danger-50 dark:border-danger-800 dark:bg-danger-900/20">
						<CardContent className="flex items-center gap-4">
							<XCircle className="h-8 w-8 text-danger-600 dark:text-danger-400" />
							<div>
								<h3 className="font-semibold text-danger-800 dark:text-danger-200">
									Endorsements Marked for Removal
								</h3>
								<p className="mt-1 text-sm text-danger-700 dark:text-danger-300">
									You have one or more endorsements scheduled for removal.
									Increase your activity before the removal date to keep them.
								</p>
							</div>
						</CardContent>
					</Card>
				)}

				{/* Active Solo Endorsements */}
				{activeSolos.length > 0 && (
					<ActiveSoloEndorsements endorsements={activeSolos} />
				)}

				{/* Main Endorsement Tabs */}
				<Tabs className="w-full" defaultValue="tier1">
					<TabsList className="grid w-full grid-cols-3">
						<TabsTrigger className="flex items-center gap-2" value="tier1">
							<Shield className="h-4 w-4" />
							Tier 1 ({tier1Endorsements.length})
							{hasRemovalEndorsements && (
								<span className="ml-1 flex h-2 w-2">
									<span className="absolute inline-flex h-2 w-2 animate-ping rounded-full bg-danger-400 opacity-75"></span>
									<span className="relative inline-flex h-2 w-2 rounded-full bg-danger-500"></span>
								</span>
							)}
						</TabsTrigger>
						<TabsTrigger className="flex items-center gap-2" value="tier2">
							<Shield className="h-4 w-4" />
							Tier 2 ({tier2Endorsements.length})
						</TabsTrigger>
						<TabsTrigger className="flex items-center gap-2" value="solo">
							<CheckCircle className="h-4 w-4" />
							Solo ({soloEndorsements.length})
						</TabsTrigger>
					</TabsList>

					<TabsContent className="mt-1 space-y-4" value="tier1">
						<Card>
							<CardHeader>
								<CardTitle>Tier 1 Endorsements</CardTitle>
								<CardDescription>
									Position specific endorsements requiring regular activity to
									maintain. Minimum activity thresholds must be met to avoid
									removal.
								</CardDescription>
							</CardHeader>
							<CardContent>
								{tier1Endorsements.length > 0 ? (
									<Tier1EndorsementsTable endorsements={tier1Endorsements} />
								) : (
									<div className="py-8 text-center">
										<Shield className="mx-auto h-12 w-12 text-muted-foreground" />
										<h3 className="mt-2 text-sm font-medium text-foreground">
											No Tier 1 Endorsements
										</h3>
										<p className="mt-1 text-sm text-muted-foreground">
											You don't have any Tier 1 endorsements yet. Complete your
											training to receive endorsements.
										</p>
									</div>
								)}
							</CardContent>
						</Card>
					</TabsContent>

					<TabsContent className="mt-1 space-y-4" value="tier2">
						<Card>
							<CardHeader>
								<CardTitle>Tier 2 Endorsements</CardTitle>
								<CardDescription>
									Position independent endorsements that require Moodle course
									completion.
								</CardDescription>
							</CardHeader>
							<CardContent>
								{tier2Endorsements.length > 0 ? (
									<Tier2EndorsementsTable endorsements={tier2Endorsements} />
								) : (
									<div className="py-8 text-center">
										<Shield className="mx-auto h-12 w-12 text-muted-foreground" />
										<h3 className="mt-2 text-sm font-medium text-foreground">
											No Tier 2 Endorsements Available
										</h3>
										<p className="mt-1 text-sm text-muted-foreground">
											No Tier 2 endorsements are currently available or you have
											already obtained all available endorsements.
										</p>
									</div>
								)}
							</CardContent>
						</Card>
					</TabsContent>

					<TabsContent className="mt-1 space-y-4" value="solo">
						<Card>
							<CardHeader>
								<CardTitle>Solo Endorsements</CardTitle>
								<CardDescription>
									Temporary endorsements issued by mentors for specific
									positions. These have expiration dates and are used for
									training purposes.
								</CardDescription>
							</CardHeader>
							<CardContent>
								{soloEndorsements.length > 0 ? (
									<SoloEndorsementsTable endorsements={soloEndorsements} />
								) : (
									<div className="py-8 text-center">
										<CheckCircle className="mx-auto h-12 w-12 text-muted-foreground" />
										<h3 className="mt-2 text-sm font-medium text-foreground">
											No Solo Endorsements
										</h3>
										<p className="mt-1 text-sm text-muted-foreground">
											You don't have any active solo endorsements. Your mentor
											will issue solo endorsements during training.
										</p>
									</div>
								)}
							</CardContent>
						</Card>
					</TabsContent>
				</Tabs>

				{/* Activity Requirements Info */}
				<Card className="border-primary/20 bg-primary/10 dark:border-primary/40 dark:bg-primary/20">
					<CardContent className="flex items-start gap-4">
						<div className="rounded-full bg-primary/10 p-2 dark:bg-primary/20">
							<AlertCircle className="h-5 w-5 text-primary dark:text-primary" />
						</div>
						<div>
							<h3 className="font-semibold text-foreground">
								Activity Requirements
							</h3>
							<p className="mt-1 text-sm text-muted-foreground">
								Maintain minimum activity hours to keep your Tier 1 endorsements
								active. Low activity endorsements may be marked for removal by
								mentors. Once marked, you have 31 days to increase activity
								before the endorsement is removed.
							</p>
						</div>
					</CardContent>
				</Card>
			</div>
		</AppLayout>
	)
}
