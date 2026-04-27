import { useId } from "react"

interface PlaceholderPatternProps {
	className?: string
}

export function PlaceholderPattern({ className }: PlaceholderPatternProps) {
	const patternId = useId()

	return (
		<svg className={className} fill="none">
			<defs>
				<pattern
					height="10"
					id={patternId}
					patternUnits="userSpaceOnUse"
					width="10"
					x="0"
					y="0"
				>
					<path d="M-3 13 15-5M-5 5l18-18M-1 21 17 3"></path>
				</pattern>
			</defs>
			<rect
				fill={`url(#${patternId})`}
				height="100%"
				stroke="none"
				width="100%"
			></rect>
		</svg>
	)
}
