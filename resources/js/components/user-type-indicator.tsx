import { usePage } from "@inertiajs/react"
import { Shield, User } from "lucide-react"
import type { SharedData } from "@/types"

export default function UserTypeIndicator() {
	const { auth } = usePage<SharedData>().props
	const user = auth.user

	if (!user) return null

	if (user.is_admin) {
		return (
			<div className="flex items-center gap-2 rounded-full bg-accent-100 px-3 py-1 text-sm font-medium text-accent-800 dark:bg-accent-900 dark:text-accent-300">
				<Shield className="h-4 w-4" />
				Admin Access
			</div>
		)
	}

	if (user.is_vatsim_user) {
		return (
			<div className="flex items-center gap-2 rounded-full bg-primary-100 px-3 py-1 text-sm font-medium text-primary-800 dark:bg-primary-900 dark:text-primary-300">
				<User className="h-4 w-4" />
				VATSIM: {user.vatsim_id}
			</div>
		)
	}

	return null
}
