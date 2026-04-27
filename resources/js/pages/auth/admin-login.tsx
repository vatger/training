import { Head, useForm } from "@inertiajs/react"
import { ArrowLeft, Shield } from "lucide-react"
import type { FormEvent } from "react"
import { Button } from "@/components/ui/button"
import {
	Card,
	CardContent,
	CardDescription,
	CardHeader,
	CardTitle,
} from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"

export default function AdminLogin() {
	const { data, setData, post, processing, errors, reset } = useForm({
		email: "",
		password: "",
		remember: true,
	})

	const submit = (e: FormEvent) => {
		e.preventDefault()

		post("/admin/login", {
			onFinish: () => reset("password"),
		})
	}

	return (
		<>
			<Head title="Admin Login" />

			<div className="bg-body flex min-h-screen items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
				<Card className="w-full max-w-md">
					<CardHeader className="space-y-1">
						<div className="mb-4 flex items-center justify-center">
							<div className="rounded-full bg-red-100 p-3 dark:bg-red-900/20">
								<Shield className="h-8 w-8 text-red-600 dark:text-red-400" />
							</div>
						</div>
						<CardTitle className="text-center text-2xl font-bold text-red-600 dark:text-red-400">
							Administrator Access
						</CardTitle>
						<CardDescription className="text-center">
							Development and administrator access only
						</CardDescription>
					</CardHeader>
					<CardContent className="space-y-4">
						<form className="space-y-4" onSubmit={submit}>
							<div className="space-y-2">
								<Label htmlFor="email">Email</Label>
								<Input
									autoComplete="username"
									autoFocus
									className="block w-full"
									id="email"
									name="email"
									onChange={(e) => setData("email", e.target.value)}
									type="email"
									value={data.email}
								/>
								{errors.email && (
									<p className="text-sm text-red-600">{errors.email}</p>
								)}
							</div>

							<div className="space-y-2">
								<Label htmlFor="password">Password</Label>
								<Input
									autoComplete="current-password"
									className="block w-full"
									id="password"
									name="password"
									onChange={(e) => setData("password", e.target.value)}
									type="password"
									value={data.password}
								/>
								{errors.password && (
									<p className="text-sm text-red-600">{errors.password}</p>
								)}
							</div>

							<Button
								className="w-full bg-red-600 hover:bg-red-700"
								disabled={processing}
								type="submit"
							>
								{processing ? "Signing in..." : "Sign in as Admin"}
							</Button>
						</form>

						<div className="text-center">
							<a
								className="inline-flex items-center text-sm text-gray-600 hover:text-gray-500"
								href="/"
							>
								<ArrowLeft className="mr-2 h-4 w-4" />
								Back to VATSIM Login
							</a>
						</div>
					</CardContent>
				</Card>
			</div>
		</>
	)
}
