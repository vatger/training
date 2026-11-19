import "../css/app.css"

import { createInertiaApp, type ResolvedComponent } from "@inertiajs/react"
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers"
import { createRoot } from "react-dom/client"

const appName = import.meta.env.VITE_APP_NAME || "vatger Training System"

const pages = import.meta.glob<{ default: ResolvedComponent }>(
	"./pages/**/*.tsx",
)

const resolve = (name: string) =>
	resolvePageComponent(`./pages/${name}.tsx`, pages).then(
		(module) => module.default,
	)

createInertiaApp({
	title: (title) => `${title} - ${appName}`,
	resolve,
	setup({ el, App, props }) {
		const root = createRoot(el)

		root.render(<App {...props} />)
	},
	progress: {
		color: "oklch(0.576 0.074 251.72)",
	},
})
