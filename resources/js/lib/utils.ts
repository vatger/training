import { type ClassValue, clsx } from "clsx"
import { twMerge } from "tailwind-merge"

export function cn(...inputs: ClassValue[]) {
	return twMerge(clsx(inputs))
}

export function formatActivityHours(decimalHours: number): string {
	const hours = Math.floor(decimalHours)
	const minutes = Math.round((decimalHours - hours) * 60)
	return `${hours}:${minutes.toString().padStart(2, "0")}`
}
