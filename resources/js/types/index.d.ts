import type { InertiaLinkProps } from "@inertiajs/react"
import type { LucideIcon } from "lucide-react"

export interface BreadcrumbItem {
	title: string
	href: string
}

export interface NavGroup {
	title: string
	items: NavItem[]
}

export interface NavItem {
	title: string
	href: NonNullable<InertiaLinkProps["href"]>
	icon?: LucideIcon | null
	isActive?: boolean
	external?: boolean
}

export type Endorsement = {
	id?: number
	position: string
	fullName: string
	activity?: number
	requiredActivity?: number
	status: "active" | "removal" | "warning" | "completed" | "available"
	lastActivity?: string
	type: "GNDDEL" | "TWR" | "APP" | "CTR"
	expiresAt?: string
	mentor?: string
	removalDate?: string
	lastUpdated?: string
	moodleCourseId?: number
	hasEndorsement?: boolean
	moodleCompleted?: boolean
}
