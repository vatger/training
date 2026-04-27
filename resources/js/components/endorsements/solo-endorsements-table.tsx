import { Clock } from "lucide-react"
import { getPositionIcon, getStatusBadge } from "@/pages/endorsements/trainee"
import type { Endorsement } from "@/types"
import {
	Table,
	TableBody,
	TableCell,
	TableHead,
	TableHeader,
	TableRow,
} from "../ui/table"

export default function SoloEndorsementsTable({
	endorsements,
}: {
	endorsements: Endorsement[]
}) {
	return (
		<div className="rounded-md border">
			<Table>
				<TableHeader>
					<TableRow>
						<TableHead>Position</TableHead>
						<TableHead>Mentor</TableHead>
						<TableHead>Expires</TableHead>
						<TableHead>Status</TableHead>
					</TableRow>
				</TableHeader>
				<TableBody>
					{endorsements.map((endorsement) => (
						<TableRow className="h-18" key={endorsement.position}>
							<TableCell>
								<div className="flex items-center gap-3">
									<div className="flex h-8 w-8 items-center justify-center rounded-full bg-orange-100 text-orange-600">
										{getPositionIcon(endorsement.type)}
									</div>
									<div>
										<div className="font-medium">{endorsement.position}</div>
									</div>
								</div>
							</TableCell>
							<TableCell>
								<div>
									<div className="font-medium">{endorsement.mentor}</div>
								</div>
							</TableCell>
							<TableCell>
								{endorsement.expiresAt ? (
									<div className="flex items-center gap-2 text-sm text-muted-foreground">
										<Clock className="h-4 w-4" />
										{new Date(endorsement.expiresAt).toLocaleDateString("de")}
									</div>
								) : (
									<span className="text-muted-foreground">—</span>
								)}
							</TableCell>
							<TableCell>{getStatusBadge(endorsement.status)}</TableCell>
						</TableRow>
					))}
				</TableBody>
			</Table>
		</div>
	)
}
