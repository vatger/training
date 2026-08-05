import { Plane, Radio, Shield, TowerControl } from "lucide-react"

export type PositionType = "GND" | "TWR" | "APP" | "CTR"
export type CourseType = "RTG" | "EDMT" | "FAM" | "GST" | "RST"
export type StatusType = "active" | "warning" | "removal" | "available"

/**
 * Get color classes for position badges
 */
export const getPositionColor = (position: string): string => {
	switch (position) {
		case "GND":
			return "bg-secondary text-secondary-foreground"
		case "TWR":
			return "bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300"
		case "APP":
			return "bg-success-100 text-success-700 dark:bg-success-900 dark:text-success-300"
		case "CTR":
			return "bg-accent-100 text-accent-700 dark:bg-accent-900 dark:text-accent-300"
		default:
			return "bg-secondary text-secondary-foreground"
	}
}

/**
 * Get color classes for course type badges
 */
export const getTypeColor = (type: string): string => {
	switch (type) {
		case "RTG":
			return "bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-300 border-primary-200 dark:border-primary-800"
		case "EDMT":
			return "bg-accent-100 text-accent-800 dark:bg-accent-900 dark:text-accent-300 border-accent-200 dark:border-accent-800"
		case "FAM":
			return "bg-warning-100 text-warning-800 dark:bg-warning-900 dark:text-warning-300 border-warning-200 dark:border-warning-800"
		case "GST":
			return "bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-300 border-success-200 dark:border-success-800"
		case "RST":
			return "bg-danger-100 text-danger-800 dark:bg-danger-900 dark:text-danger-300 border-danger-200 dark:border-danger-800"
		default:
			return "border-border bg-secondary text-secondary-foreground"
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
			return "border-warning-200 bg-warning-50 text-warning-800 dark:border-warning-700 dark:bg-warning-900 dark:text-warning-300"
		case "removal":
			return "border-danger-200 bg-danger-50 text-danger-700 dark:border-danger-700 dark:bg-danger-900 dark:text-danger-300"
		case "available":
			return "border-primary-200 bg-primary-50 text-primary-700 dark:border-primary-700 dark:bg-primary-900 dark:text-primary-300"
		case "completed":
			return "border-success-200 bg-success-50 text-success-700 dark:border-success-700 dark:bg-success-900 dark:text-success-300"
		default:
			return "border-border bg-secondary text-secondary-foreground"
	}
}

/**
 * Get icon component for position type
 */
export const getPositionIcon = (position: string) => {
	switch (position) {
		case "GND":
		case "GNDDEL":
			return <Radio className="h-4 w-4" />
		case "TWR":
			return <TowerControl className="h-4 w-4" />
		case "APP":
			return <Shield className="h-4 w-4" />
		case "CTR":
			return <Plane className="h-4 w-4" />
		default:
			return <Radio className="h-4 w-4" />
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
