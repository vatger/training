import type { ComponentPropsWithoutRef } from "react"
import { Icon } from "@/components/icon"
import {
	SidebarGroup,
	SidebarGroupContent,
	SidebarMenu,
	SidebarMenuButton,
	SidebarMenuItem,
} from "@/components/ui/sidebar"
import type { NavItem } from "@/types"

export function NavFooter({
	items,
	className,
	...props
}: ComponentPropsWithoutRef<typeof SidebarGroup> & {
	items: NavItem[]
}) {
	return (
		<SidebarGroup
			{...props}
			className={`group-data-[collapsible=icon]:p-0 ${className || ""}`}
		>
			<SidebarGroupContent>
				<SidebarMenu>
					{items.map((item) => (
						<SidebarMenuItem key={item.title}>
							<SidebarMenuButton
								asChild
								className="text-secondary-600 hover:text-secondary-800 dark:text-secondary-300 dark:hover:text-secondary-100"
							>
								<a
									href={
										typeof item.href === "string" ? item.href : item.href.url
									}
									rel="noopener noreferrer"
									target="_blank"
								>
									{item.icon && (
										<Icon className="h-5 w-5" iconNode={item.icon} />
									)}
									<span>{item.title}</span>
								</a>
							</SidebarMenuButton>
						</SidebarMenuItem>
					))}
				</SidebarMenu>
			</SidebarGroupContent>
		</SidebarGroup>
	)
}
