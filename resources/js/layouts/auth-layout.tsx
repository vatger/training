import AuthLayoutTemplate from "@/layouts/auth/auth-simple-layout"

export default function AuthLayout({
	children,
	title,
	description,
	...props
}: {
	children: React.ReactNode
	title: string
	description: string
}) {
	return (
		<AuthLayoutTemplate description={description} title={title} {...props}>
			{children}
		</AuthLayoutTemplate>
	)
}
