import { Head } from "@inertiajs/react"
import { LogIn } from "lucide-react"
import { Alert, AlertDescription } from "@/components/ui/alert"
import { Button } from "@/components/ui/button"
import {
	Card,
	CardContent,
	CardDescription,
	CardHeader,
	CardTitle,
} from "@/components/ui/card"

interface LoginProps {
	status?: string
	sandboxLoginEnabled?: boolean
}

export default function Login({ status, sandboxLoginEnabled }: LoginProps) {
	const handleVatsimLogin = () => {
		window.location.href = "/auth/vatsim"
	}

	const handleSandboxLogin = () => {
		window.location.href = "/auth/vatsim/sandbox"
	}

	return (
		<>
			<Head title="Log in" />

			<div className="bg-body flex min-h-screen items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
				<Card className="w-full max-w-md">
					<CardHeader className="space-y-1">
						<CardTitle className="text-center text-2xl font-bold">
							Sign in to your account
						</CardTitle>
						<CardDescription className="text-center">
							Access the vatger Training System with your VATSIM account
						</CardDescription>
					</CardHeader>
					<CardContent className="space-y-4">
						{status && (
							<Alert>
								<AlertDescription>{status}</AlertDescription>
							</Alert>
						)}

						{/* VATSIM OAuth Button */}
						<Button
							className="w-full"
							onClick={handleVatsimLogin}
							size="lg"
							type="button"
							variant="accent"
						>
							<LogIn />
							Login with vatger Connect
						</Button>

						{/* VATSIM Connect sandbox login — only rendered when the backend
						    confirms this is a non-production, allowed dev host. */}
						{sandboxLoginEnabled && (
							<div className="mt-6 space-y-2 rounded-md border border-accent-200 bg-accent-100 p-3">
								<p className="text-xs text-accent-800">
									<strong>Development Mode:</strong> Sign in using the VATSIM
									Connect sandbox instead of production vatger Connect.
								</p>
								<Button
									className="w-full"
									onClick={handleSandboxLogin}
									size="sm"
									type="button"
									variant="outline"
								>
									Login with VATSIM Sandbox
								</Button>
							</div>
						)}
					</CardContent>
				</Card>
			</div>
		</>
	)
}
