import { usePage } from "@inertiajs/react"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { useInitials } from "@/hooks/use-initials"
import type { SharedData, User } from "@/types"

export function UserInfo({
	showEmail = false,
}: {
	user: User
	showEmail?: boolean
}) {
	const getInitials = useInitials()
	const { auth } = usePage<SharedData>().props
	const user = auth.user

	if (!user) return null

	return (
		<>
			<Avatar className="h-8 w-8 overflow-hidden rounded-full">
				<AvatarImage alt={user.name} src={user.avatar} />
				{user.is_admin ? (
					<>
						<AvatarFallback className="rounded-full bg-accent-200 text-accent-950 dark:bg-accent-800/70 dark:text-accent-50">
							{getInitials(user.name)}
						</AvatarFallback>
					</>
				) : (
					<>
						<AvatarFallback className="rounded-full bg-secondary text-secondary-foreground">
							{getInitials(user.name)}
						</AvatarFallback>
					</>
				)}
			</Avatar>
			<div className="grid flex-1 text-left text-sm leading-tight">
				<span className="truncate font-medium">{user.name}</span>
				{showEmail && (
					<span className="truncate text-xs text-muted-foreground">
						{user.email}
					</span>
				)}
			</div>
		</>
	)
}
