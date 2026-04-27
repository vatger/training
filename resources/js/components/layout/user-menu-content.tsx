import { Link, router } from "@inertiajs/react"
import { LogOut, Settings } from "lucide-react"
import { UserInfo } from "@/components/layout/user-info"
import {
	DropdownMenuItem,
	DropdownMenuLabel,
	DropdownMenuSeparator,
} from "@/components/ui/dropdown-menu"
import { useMobileNavigation } from "@/hooks/use-mobile-navigation"
import { logout } from "@/routes"
import type { User } from "@/types"

interface UserMenuContentProps {
	user: User
}

export function UserMenuContent({ user }: UserMenuContentProps) {
	const cleanup = useMobileNavigation()

	const handleLogout = () => {
		cleanup()
		router.flushAll()
	}

	return (
		<>
			<DropdownMenuLabel className="p-0 font-normal">
				<div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
					<UserInfo showEmail={true} user={user} />
				</div>
			</DropdownMenuLabel>
			<DropdownMenuSeparator />
			<DropdownMenuItem asChild>
				<Link className="block w-full" href={route("settings.index")}>
					<Settings className="mr-2" />
					Settings
				</Link>
			</DropdownMenuItem>
			<DropdownMenuSeparator />
			<DropdownMenuItem asChild>
				<Link
					as="button"
					className="block w-full"
					data-test="logout-button"
					href={logout()}
					onClick={handleLogout}
				>
					<LogOut className="mr-2" />
					Log out
				</Link>
			</DropdownMenuItem>
		</>
	)
}
