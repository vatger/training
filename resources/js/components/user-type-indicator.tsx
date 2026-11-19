import { usePage } from "@inertiajs/react"
import { Shield, User } from "lucide-react"

export default function UserTypeIndicator() {
	const { auth } = usePage().props
	const user = auth.user

	if (!user) return null

	if (user.is_admin) {
		return (
			<div className="flex items-center gap-2 rounded-full bg-danger-100 px-3 py-1 text-sm font-medium text-danger-700">
				<Shield className="h-4 w-4" />
				Admin Access
			</div>
		)
	}

	return (
		<div className="flex items-center gap-2 rounded-full bg-primary-100 px-3 py-1 text-sm font-medium text-primary-700">
			<User className="h-4 w-4" />
			VATSIM: {user.vatsim_id}
		</div>
	)
}
