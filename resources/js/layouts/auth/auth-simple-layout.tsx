import { Link } from "@inertiajs/react"
import type { PropsWithChildren } from "react"
import logoDark from "@/images/vatger-training-dark.svg"
import logoLight from "@/images/vatger-training-light.svg"
import { dashboard } from "@/routes"

interface AuthLayoutProps {
	name?: string
	title?: string
	description?: string
}

export default function AuthSimpleLayout({
	children,
	title,
	description,
}: PropsWithChildren<AuthLayoutProps>) {
	return (
		<div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
			<div className="w-full max-w-sm">
				<div className="flex flex-col gap-8">
					<div className="flex flex-col items-center gap-4">
						<Link
							className="flex flex-col items-center gap-2 font-medium"
							href={dashboard()}
						>
							<div className="mb-1 flex items-center justify-center">
								<img
									alt="vatger Training"
									className="h-8 w-auto dark:hidden"
									src={logoLight}
								/>
								<img
									alt="vatger Training"
									className="hidden h-8 w-auto dark:block"
									src={logoDark}
								/>
							</div>
							<span className="sr-only">{title}</span>
						</Link>

						<div className="space-y-2 text-center">
							<h1 className="text-xl font-medium">{title}</h1>
							<p className="text-center text-sm text-muted-foreground">
								{description}
							</p>
						</div>
					</div>
					{children}
				</div>
			</div>
		</div>
	)
}
