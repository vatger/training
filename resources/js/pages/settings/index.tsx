import { Head, useForm } from "@inertiajs/react"
import { Bell, Globe, Monitor, Moon, Save, Sun } from "lucide-react"
import { useState } from "react"
import { toast } from "sonner"
import { Button } from "@/components/ui/button"
import {
	Card,
	CardContent,
	CardDescription,
	CardHeader,
	CardTitle,
} from "@/components/ui/card"
import { Label } from "@/components/ui/label"
import { Switch } from "@/components/ui/switch"
import AppLayout from "@/layouts/app-layout"
import { cn } from "@/lib/utils"
import type { BreadcrumbItem } from "@/types"

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: "Settings",
		href: route("settings.index"),
	},
]

interface Settings {
	theme: "light" | "dark" | "system"
	english_only: boolean
	notification_preferences: Record<string, boolean>
}

interface Props {
	settings: Settings
}

const notificationTypes = [
	{
		key: "training_started",
		label: "Training Started",
		description: "When a mentor starts your training",
	},
	{
		key: "waiting_list_joined",
		label: "Waiting List Joined",
		description: "When you join a waiting list",
	},
	{
		key: "waiting_list_left",
		label: "Waiting List Left",
		description: "When you leave a waiting list",
	},
	{
		key: "endorsement_granted",
		label: "Endorsement Granted",
		description: "When you receive a new endorsement",
	},
	{
		key: "endorsement_removal",
		label: "Endorsement Removal",
		description: "When an endorsement is marked for removal",
	},
	{
		key: "solo_granted",
		label: "Solo Granted",
		description: "When you receive a solo endorsement",
	},
	{
		key: "solo_extended",
		label: "Solo Extended",
		description: "When your solo endorsement is extended",
	},
	{
		key: "course_completed",
		label: "Course Completed",
		description: "When you complete a course",
	},
	{
		key: "training_log_created",
		label: "Training Log Created",
		description: "When a mentor creates a training log",
	},
	{
		key: "cpt_scheduled",
		label: "CPT Scheduled",
		description: "When a CPT is scheduled for you",
	},
	{
		key: "cpt_graded",
		label: "CPT Graded",
		description: "When your CPT is graded",
	},
]

export default function SettingsPage({ settings: initialSettings }: Props) {
	const [initialTheme] = useState(initialSettings.theme)
	const { data, setData, post, processing } = useForm<Settings>({
		theme: initialSettings.theme,
		english_only: initialSettings.english_only,
		notification_preferences: initialSettings.notification_preferences,
	})

	const handleSubmit = (e: React.FormEvent) => {
		e.preventDefault()

		const themeChanged = data.theme !== initialTheme

		post(route("settings.update"), {
			preserveScroll: true,
			onSuccess: () => {
				toast.success("Settings saved successfully")

				// Force a full page reload if theme changed to apply it
				if (themeChanged) {
					setTimeout(() => {
						window.location.reload()
					}, 500)
				}
			},
			onError: () => {
				toast.error("Failed to save settings")
			},
		})
	}

	const handleNotificationToggle = (key: string, enabled: boolean) => {
		setData("notification_preferences", {
			...data.notification_preferences,
			[key]: enabled,
		})
	}

	const themeOptions = [
		{ value: "light" as const, label: "Light", icon: Sun },
		{ value: "dark" as const, label: "Dark", icon: Moon },
		{ value: "system" as const, label: "System", icon: Monitor },
	]

	return (
		<AppLayout breadcrumbs={breadcrumbs}>
			<Head title="Settings" />

			<div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
				<form className="space-y-6" onSubmit={handleSubmit}>
					<Card>
						<CardHeader>
							<CardTitle className="flex items-center gap-2">
								<Sun className="h-5 w-5" />
								Appearance
							</CardTitle>
							<CardDescription>
								Choose how the site looks for you
							</CardDescription>
						</CardHeader>
						<CardContent>
							<div className="space-y-4">
								<Label>Theme</Label>
								<div className="grid grid-cols-3 gap-3">
									{themeOptions.map((option) => {
										const Icon = option.icon
										const isSelected = data.theme === option.value

										return (
											<button
												className={cn(
													"flex flex-col items-center gap-2 rounded-lg border-2 p-4 transition-colors",
													isSelected
														? "border-primary bg-primary/10"
														: "border-border hover:border-primary/50",
												)}
												key={option.value}
												onClick={() => setData("theme", option.value)}
												type="button"
											>
												<Icon
													className={cn(
														"h-6 w-6",
														isSelected
															? "text-primary"
															: "text-muted-foreground",
													)}
												/>
												<span
													className={cn(
														"text-sm font-medium",
														isSelected ? "text-primary" : "text-foreground",
													)}
												>
													{option.label}
												</span>
											</button>
										)
									})}
								</div>
								{data.theme !== initialTheme && (
									<div className="rounded-md bg-blue-50 p-3 dark:bg-blue-900/20">
										<p className="text-sm text-blue-800 dark:text-blue-200">
											Page will reload after saving to apply the new theme
										</p>
									</div>
								)}
							</div>
						</CardContent>
					</Card>

					<Card>
						<CardHeader>
							<CardTitle className="flex items-center gap-2">
								<Globe className="h-5 w-5" />
								Language Preferences
							</CardTitle>
							<CardDescription>
								Set your language preferences for waiting lists
							</CardDescription>
						</CardHeader>
						<CardContent>
							<div className="flex items-center justify-between rounded-lg border p-4">
								<div className="space-y-0.5">
									<Label
										className="text-base font-medium"
										htmlFor="english-only"
									>
										English Only
									</Label>
									<p className="text-sm text-muted-foreground">
										Inform Mentors your training can be held in english only
									</p>
								</div>
								<Switch
									checked={data.english_only}
									id="english-only"
									onCheckedChange={(checked) =>
										setData("english_only", checked)
									}
								/>
							</div>
						</CardContent>
					</Card>

					<Card className="hidden">
						{" "}
						{/* // TODO: Hidden for now */}
						<CardHeader>
							<CardTitle className="flex items-center gap-2">
								<Bell className="h-5 w-5" />
								Notifications
							</CardTitle>
							<CardDescription>
								Choose which notifications you want to receive
							</CardDescription>
						</CardHeader>
						<CardContent>
							<div className="space-y-4">
								{notificationTypes.map((notification) => (
									<div
										className="flex items-center justify-between rounded-lg border p-4"
										key={notification.key}
									>
										<div className="space-y-0.5">
											<Label
												className="text-base font-medium"
												htmlFor={notification.key}
											>
												{notification.label}
											</Label>
											<p className="text-sm text-muted-foreground">
												{notification.description}
											</p>
										</div>
										<Switch
											checked={
												data.notification_preferences[notification.key] ?? true
											}
											id={notification.key}
											onCheckedChange={(checked) =>
												handleNotificationToggle(notification.key, checked)
											}
										/>
									</div>
								))}
							</div>
						</CardContent>
					</Card>

					<div className="flex items-center justify-end rounded-lg border bg-muted/50 p-4">
						<Button disabled={processing} type="submit">
							<Save className="mr-2 h-4 w-4" />
							{processing ? "Saving..." : "Save Changes"}
						</Button>
					</div>
				</form>
			</div>
		</AppLayout>
	)
}
