import { createInertiaApp, type ResolvedComponent } from "@inertiajs/react"
import createServer from "@inertiajs/react/server"
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers"
import ReactDOMServer from "react-dom/server"

const appName = import.meta.env.VITE_APP_NAME || "vatger Training System"

declare global {
	var route: typeof route
}

globalThis.route = route

const pages = import.meta.glob<{ default: ResolvedComponent }>(
	"./pages/**/*.tsx",
)

const resolve = (name: string) =>
	resolvePageComponent(`./pages/${name}.tsx`, pages).then(
		(module) => module.default,
	)

createServer((page) =>
	createInertiaApp({
		page,
		render: ReactDOMServer.renderToString,
		title: (title) => (title ? `${title} - ${appName}` : appName),
		resolve,
		setup: ({ App, props }) => {
			return <App {...props} />
		},
	}),
)
