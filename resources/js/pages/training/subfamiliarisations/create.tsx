import { Head, router, useForm } from "@inertiajs/react"
import { AlertTriangle } from "lucide-react"
import { useMemo } from "react"
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader } from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from "@/components/ui/select"
import { Textarea } from "@/components/ui/textarea"
import AppLayout from "@/layouts/app-layout"
import type { BreadcrumbItem } from "@/types"

interface Subfamiliarisation {
	id: number
	name: string
	moodleComplete: boolean
}

interface PageProps {
	trainee: {
		id: number
		name: string
		vatsim_id: number
	}
	course: {
		id: number
		name: string
	}
	subfamiliarisations: Subfamiliarisation[]
}

const breadcrumbs: BreadcrumbItem[] = [
	{
		title: "Mentor Overview",
		href: route("overview.index"),
	},
	{
		title: "Log Subfamiliarisation Session",
		href: "#",
	},
]

export default function SubfamiliarisationSessionCreate({
	trainee,
	course,
	subfamiliarisations,
}: PageProps) {
	const today = new Date().toISOString().slice(0, 10)

	const { data, setData, post, processing, errors } = useForm({
		subfamiliarisation_id:
			subfamiliarisations.length === 1
				? subfamiliarisations[0].id.toString()
				: "",
		user_id: trainee.id.toString(),
		course_id: course.id.toString(),
		completed_at: today,
		remarks: "",
	})

	const handleSubmit = (e: React.FormEvent) => {
		e.preventDefault()
		post(route("subfamiliarisation-sessions.store"))
	}

	const selectedSubfamiliarisation = useMemo(
		() =>
			subfamiliarisations.find(
				(sub) => sub.id.toString() === data.subfamiliarisation_id,
			),
		[subfamiliarisations, data.subfamiliarisation_id],
	)

	const moodleIncomplete =
		selectedSubfamiliarisation !== undefined &&
		!selectedSubfamiliarisation.moodleComplete

	return (
		<AppLayout breadcrumbs={breadcrumbs}>
			<Head title="Log Subfamiliarisation Session" />
			<div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
				<div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
					<div className="lg:col-span-2">
						<Card>
							<CardHeader>
								<h2 className="text-xl font-semibold">
									Log Subfamiliarisation Session
								</h2>
								<p className="text-sm text-muted-foreground">
									{trainee.name} ({trainee.vatsim_id}) &middot; {course.name}
								</p>
								{selectedSubfamiliarisation && (
									<p className="text-sm font-medium text-foreground">
										Subfamiliarisation: {selectedSubfamiliarisation.name}
									</p>
								)}
							</CardHeader>
							<CardContent>
								<form className="space-y-6" onSubmit={handleSubmit}>
									<div className="space-y-2">
										<Label htmlFor="subfamiliarisation_id">
											Subfamiliarisation{" "}
											<span className="text-danger-600">*</span>
										</Label>
										{subfamiliarisations.length > 1 ? (
											<Select
												onValueChange={(value) =>
													setData("subfamiliarisation_id", value)
												}
												value={data.subfamiliarisation_id}
											>
												<SelectTrigger id="subfamiliarisation_id">
													<SelectValue placeholder="-- Select Subfamiliarisation --" />
												</SelectTrigger>
												<SelectContent>
													{subfamiliarisations.map((sub) => (
														<SelectItem key={sub.id} value={sub.id.toString()}>
															{sub.name}
														</SelectItem>
													))}
												</SelectContent>
											</Select>
										) : (
											<Input
												disabled
												id="subfamiliarisation_id"
												value={
													selectedSubfamiliarisation?.name ??
													"No subfamiliarisation available"
												}
											/>
										)}
										{errors.subfamiliarisation_id && (
											<p className="text-sm text-danger-600">
												{errors.subfamiliarisation_id}
											</p>
										)}
									</div>

									{moodleIncomplete && (
										<Alert variant="warning">
											<AlertTriangle />
											<AlertTitle>Moodle courses not completed</AlertTitle>
											<AlertDescription>
												This trainee has not yet completed all of the Moodle
												courses required for this subfamiliarisation. The
												session cannot be logged until they have.
											</AlertDescription>
										</Alert>
									)}

									<div className="space-y-2">
										<Label htmlFor="completed_at">
											Session Date <span className="text-danger-600">*</span>
										</Label>
										<Input
											id="completed_at"
											max={today}
											onChange={(e) => setData("completed_at", e.target.value)}
											required
											type="date"
											value={data.completed_at}
										/>
										{errors.completed_at && (
											<p className="text-sm text-danger-600">
												{errors.completed_at}
											</p>
										)}
									</div>

									<div className="space-y-2">
										<Label htmlFor="remarks">
											Remarks{" "}
											<span className="text-muted-foreground">(Optional)</span>
										</Label>
										<Textarea
											id="remarks"
											onChange={(e) => setData("remarks", e.target.value)}
											rows={4}
											value={data.remarks}
										/>
										{errors.remarks && (
											<p className="text-sm text-danger-600">
												{errors.remarks}
											</p>
										)}
										<p className="text-xs text-muted-foreground">
											This confirms the trainee has completed the practical
											session required for combined control under this
											subfamiliarisation. You will be recorded as the mentor who
											signed it off.
										</p>
									</div>

									<div className="flex justify-end gap-3 border-t pt-6">
										<Button
											onClick={() => router.visit(route("overview.index"))}
											type="button"
											variant="outline"
										>
											Cancel
										</Button>
										<Button
											disabled={processing || moodleIncomplete}
											type="submit"
										>
											{processing ? "Logging..." : "Log Session"}
										</Button>
									</div>
								</form>
							</CardContent>
						</Card>
					</div>
				</div>
			</div>
		</AppLayout>
	)
}
