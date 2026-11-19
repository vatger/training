import "@inertiajs/core"

declare module "@inertiajs/core" {
	export interface InertiaConfig {
		sharedPageProps: App.Data.SharedData
	}
}
