import "../css/app.css"

import { createInertiaApp, type ResolvedComponent } from "@inertiajs/react"
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers"
import { createRoot } from "react-dom/client"
// biome-ignore lint/correctness/noUnusedImports: assigned to window.route at runtime
import { route } from "ziggy-js"

const appName = import.meta.env.VITE_APP_NAME || "vatger Training System"

// Make route function globally available
declare global {
	// biome-ignore lint/suspicious/noRedeclare: no factor
	var route: typeof route
}

window.route = route

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
