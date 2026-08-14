import { Plane, Radar, Radio, TowerControl } from "lucide-react"
import { Badge } from "@/components/ui/badge"
import { cn } from "@/lib/utils"

export type PositionType = "GND" | "TWR" | "APP" | "CTR"
export type CourseType = "RTG" | "EDMT" | "FAM" | "GST" | "RST"
export type StatusType = "active" | "warning" | "removal" | "available"

/**
 * Get fill color classes for position badges/icon backgrounds
 */
export const getPositionColor = (position: string): string => {
	switch (position) {
		case "GND":
		case "GNDDEL":
			return "bg-secondary-100 text-secondary-700 dark:bg-secondary-900 dark:text-secondary-300"
		case "TWR":
			return "bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300"
		case "APP":
			return "bg-success-100 text-success-700 dark:bg-success-900 dark:text-success-300"
		case "CTR":
			return "bg-accent-100 text-accent-700 dark:bg-accent-900 dark:text-accent-300"
		default:
			return "bg-secondary-100 text-secondary-700 dark:bg-secondary-900 dark:text-secondary-300"
	}
}

/**
 * Get color classes for status badges
 */
export const getStatusColor = (status: string): string => {
	switch (status) {
		case "active":
			return "border-success-200 bg-success-50 text-success-700 dark:border-success-700 dark:bg-success-900 dark:text-success-300"
		case "warning":
			return "border-warning-200 bg-warning-50 text-warning-700 dark:border-warning-700 dark:bg-warning-900 dark:text-warning-300"
		case "removal":
			return "border-danger-200 bg-danger-50 text-danger-700 dark:border-danger-700 dark:bg-danger-900 dark:text-danger-300"
		case "available":
			return "border-primary-200 bg-primary-50 text-primary-700 dark:border-primary-700 dark:bg-primary-900 dark:text-primary-300"
		case "completed":
			return "border-primary-200 bg-primary-50 text-primary-700 dark:border-primary-700 dark:bg-primary-900 dark:text-primary-300"
		default:
			return "border-secondary-200 bg-secondary-50 text-secondary-700 dark:border-secondary-700 dark:bg-secondary-900 dark:text-secondary-300"
	}
}

/**
 * Get icon component for position type. Each position has a shape distinct
 * from every other position AND from every course type icon, so it can be
 * told apart without relying on color.
 */
export const getPositionIcon = (position: string, className = "h-4 w-4") => {
	switch (position) {
		case "GND":
		case "GNDDEL":
			return <Radio className={className} />
		case "TWR":
			return <TowerControl className={className} />
		case "APP":
			return <Radar className={className} />
		case "CTR":
			return <Plane className={className} />
		default:
			return <Radio className={className} />
	}
}

/**
 * Get display text for course type
 */
export const getCourseTypeDisplay = (type: string): string => {
	switch (type) {
		case "RTG":
			return "Rating"
		case "EDMT":
			return "Endorsement"
		case "FAM":
			return "Familiarisation"
		case "GST":
			return "Visitor"
		case "RST":
			return "Roster"
		default:
			return type
	}
}

const TYPE_CHIP_COLOR: Record<string, string> = {
	RTG: "var(--chip-rtg)",
	EDMT: "var(--chip-edmt)",
	FAM: "var(--chip-fam)",
	GST: "var(--chip-gst)",
	RST: "var(--chip-rst)",
}

const POSITION_CHIP_COLOR: Record<string, string> = {
	GND: "var(--chip-gnd)",
	GNDDEL: "var(--chip-gnd)",
	TWR: "var(--chip-twr)",
	APP: "var(--chip-app)",
	CTR: "var(--chip-ctr)",
}

const FALLBACK_CHIP_COLOR = "var(--muted-foreground)"

/**
 * A course's type and position fused into one badge: the two values that
 * together identify "what this course is" read as a single pill instead of
 * two separate badges competing for attention. Built on the app's existing
 * Badge primitive so it keeps the same shape, size and outline style as
 * every other badge in the UI — the two halves are told apart by text color
 * and a colored underline only, never a fill, so it stays visually quiet.
 *
 * The type half is always spelled out in full ("Endorsement", not "EDMT") —
 * those codes aren't widely known. The position half keeps the short code
 * (GND/TWR/APP/CTR), since those are standard, universally recognized ATC
 * abbreviations on their own.
 */
export function CourseChip({
	type,
	position,
	typeLabel,
	positionLabel,
	className,
}: {
	type: string
	position: string
	typeLabel?: string
	positionLabel?: string
	className?: string
}) {
	const typeColor = TYPE_CHIP_COLOR[type] ?? FALLBACK_CHIP_COLOR
	const positionColor = POSITION_CHIP_COLOR[position] ?? FALLBACK_CHIP_COLOR
	const resolvedTypeLabel = typeLabel ?? getCourseTypeDisplay(type)

	return (
		<Badge
			className={cn(
				"gap-0 overflow-hidden border-0 bg-transparent px-0 py-0",
				className,
			)}
			title={`${resolvedTypeLabel} · ${positionLabel ?? position}`}
			variant="outline"
		>
			<span
				className="rounded-l-full border-2 border-r-0 px-2 py-0.5 font-semibold"
				style={{ borderColor: typeColor, color: typeColor }}
			>
				{resolvedTypeLabel}
			</span>
			<span
				className="rounded-r-full border-2 px-2 py-0.5 font-semibold"
				style={{ borderColor: positionColor, color: positionColor }}
			>
				{position}
			</span>
		</Badge>
	)
}

/**
 * Position on its own, styled identically to the position half of
 * CourseChip. Use where the course type is already conveyed some other way
 * (e.g. a tab/filter already scoped to one type) so repeating it would be
 * redundant.
 */
export function PositionBadge({
	position,
	label,
	className,
}: {
	position: string
	label?: string
	className?: string
}) {
	const positionColor = POSITION_CHIP_COLOR[position] ?? FALLBACK_CHIP_COLOR

	return (
		<Badge
			className={cn(
				"gap-0 overflow-hidden border-2 bg-transparent px-2 py-0.5 font-semibold",
				className,
			)}
			style={{ borderColor: positionColor, color: positionColor }}
			variant="outline"
		>
			{label ?? position}
		</Badge>
	)
}
