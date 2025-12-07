import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\CptController::store
* @see app/Http/Controllers/CptController.php:427
* @route '/cpt/{cpt}/upload'
*/
export const store = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/cpt/{cpt}/upload',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CptController::store
* @see app/Http/Controllers/CptController.php:427
* @route '/cpt/{cpt}/upload'
*/
store.url = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { cpt: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { cpt: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            cpt: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        cpt: typeof args.cpt === 'object'
        ? args.cpt.id
        : args.cpt,
    }

    return store.definition.url
            .replace('{cpt}', parsedArgs.cpt.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CptController::store
* @see app/Http/Controllers/CptController.php:427
* @route '/cpt/{cpt}/upload'
*/
store.post = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CptController::store
* @see app/Http/Controllers/CptController.php:427
* @route '/cpt/{cpt}/upload'
*/
const storeForm = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CptController::store
* @see app/Http/Controllers/CptController.php:427
* @route '/cpt/{cpt}/upload'
*/
storeForm.post = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

store.form = storeForm

const upload = {
    store: Object.assign(store, store),
}

export default upload