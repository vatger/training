import AppLogoIcon from "./app-logo-icon"

export default function AppLogo() {
	return (
		<>
			<AppLogoIcon className="hidden size-8 shrink-0 group-data-[collapsible=icon]:block" />
			<span className="block h-9 shrink-0 group-data-[collapsible=icon]:hidden">
				<img
					alt="vatger / Training"
					className="block h-full w-auto object-contain dark:hidden"
					src="/images/brand/logo-training-on-light.svg"
				/>
				<img
					alt="vatger / Training"
					className="hidden h-full w-auto object-contain dark:block"
					src="/images/brand/logo-training-on-dark.svg"
				/>
			</span>
		</>
	)
}
