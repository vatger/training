import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\CptController::view
* @see app/Http/Controllers/CptController.php:489
* @route '/cpt/log/{log}'
*/
export const view = (args: { log: number | { id: number } } | [log: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: view.url(args, options),
    method: 'get',
})

view.definition = {
    methods: ["get","head"],
    url: '/cpt/log/{log}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CptController::view
* @see app/Http/Controllers/CptController.php:489
* @route '/cpt/log/{log}'
*/
view.url = (args: { log: number | { id: number } } | [log: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { log: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { log: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            log: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        log: typeof args.log === 'object'
        ? args.log.id
        : args.log,
    }

    return view.definition.url
            .replace('{log}', parsedArgs.log.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CptController::view
* @see app/Http/Controllers/CptController.php:489
* @route '/cpt/log/{log}'
*/
view.get = (args: { log: number | { id: number } } | [log: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: view.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CptController::view
* @see app/Http/Controllers/CptController.php:489
* @route '/cpt/log/{log}'
*/
view.head = (args: { log: number | { id: number } } | [log: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: view.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CptController::view
* @see app/Http/Controllers/CptController.php:489
* @route '/cpt/log/{log}'
*/
const viewForm = (args: { log: number | { id: number } } | [log: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: view.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CptController::view
* @see app/Http/Controllers/CptController.php:489
* @route '/cpt/log/{log}'
*/
viewForm.get = (args: { log: number | { id: number } } | [log: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: view.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CptController::view
* @see app/Http/Controllers/CptController.php:489
* @route '/cpt/log/{log}'
*/
viewForm.head = (args: { log: number | { id: number } } | [log: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: view.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

view.form = viewForm

const log = {
    view: Object.assign(view, view),
}

export default log