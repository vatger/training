import type { ComponentProps } from "react"

type AppLogoIconProps = ComponentProps<"span">

export default function AppLogoIcon({ className, ...props }: AppLogoIconProps) {
	return (
		<span className={className} {...props}>
			<img
				alt="vatger"
				className="block h-full w-auto object-contain dark:hidden"
				src="/images/brand/icon-color-on-light.svg"
			/>
			<img
				alt="vatger"
				className="hidden h-full w-auto object-contain dark:block"
				src="/images/brand/icon-color-on-dark.svg"
			/>
		</span>
	)
}
