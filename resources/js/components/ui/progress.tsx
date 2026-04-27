import * as ProgressPrimitive from "@radix-ui/react-progress"
import type * as React from "react"

import { cn } from "@/lib/utils"

interface ProgressProps
	extends React.ComponentProps<typeof ProgressPrimitive.Root> {
	colorClass?: string
}

function Progress({ className, value, colorClass, ...props }: ProgressProps) {
	return (
		<ProgressPrimitive.Root
			className={cn(
				"bg-muted relative h-2 w-full overflow-hidden rounded-full",
				className,
			)}
			data-slot="progress"
			{...props}
		>
			<ProgressPrimitive.Indicator
				className={`h-full w-full flex-1 transition-all ${colorClass}`}
				data-slot="progress-indicator"
				style={{ transform: `translateX(-${100 - (value || 0)}%)` }}
			/>
		</ProgressPrimitive.Root>
	)
}

export { Progress }
