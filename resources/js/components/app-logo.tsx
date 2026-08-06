import logoDark from "@/images/vatger-training-dark.svg"
import logoLight from "@/images/vatger-training-light.svg"

export default function AppLogo() {
	return (
		<>
			<img
				alt="vatger Training"
				className="h-auto p-2 w-auto group-data-[collapsible=icon]:hidden dark:hidden"
				src={logoLight}
			/>
			<img
				alt="vatger Training"
				className="hidden p-2 pl-0 h-auto w-auto group-data-[collapsible=icon]:hidden dark:block"
				src={logoDark}
			/>
		</>
	)
}
