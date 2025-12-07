import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\UserSearchController::search
* @see app/Http/Controllers/UserSearchController.php:13
* @route '/users/search'
*/
export const search = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: search.url(options),
    method: 'post',
})

search.definition = {
    methods: ["post"],
    url: '/users/search',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\UserSearchController::search
* @see app/Http/Controllers/UserSearchController.php:13
* @route '/users/search'
*/
search.url = (options?: RouteQueryOptions) => {
    return search.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\UserSearchController::search
* @see app/Http/Controllers/UserSearchController.php:13
* @route '/users/search'
*/
search.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: search.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\UserSearchController::search
* @see app/Http/Controllers/UserSearchController.php:13
* @route '/users/search'
*/
const searchForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: search.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\UserSearchController::search
* @see app/Http/Controllers/UserSearchController.php:13
* @route '/users/search'
*/
searchForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: search.url(options),
    method: 'post',
})

search.form = searchForm

/**
* @see \App\Http\Controllers\UserSearchController::show
* @see app/Http/Controllers/UserSearchController.php:89
* @route '/users/{vatsimId}'
*/
export const show = (args: { vatsimId: string | number } | [vatsimId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/users/{vatsimId}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\UserSearchController::show
* @see app/Http/Controllers/UserSearchController.php:89
* @route '/users/{vatsimId}'
*/
show.url = (args: { vatsimId: string | number } | [vatsimId: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { vatsimId: args }
    }

    if (Array.isArray(args)) {
        args = {
            vatsimId: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        vatsimId: args.vatsimId,
    }

    return show.definition.url
            .replace('{vatsimId}', parsedArgs.vatsimId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\UserSearchController::show
* @see app/Http/Controllers/UserSearchController.php:89
* @route '/users/{vatsimId}'
*/
show.get = (args: { vatsimId: string | number } | [vatsimId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\UserSearchController::show
* @see app/Http/Controllers/UserSearchController.php:89
* @route '/users/{vatsimId}'
*/
show.head = (args: { vatsimId: string | number } | [vatsimId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\UserSearchController::show
* @see app/Http/Controllers/UserSearchController.php:89
* @route '/users/{vatsimId}'
*/
const showForm = (args: { vatsimId: string | number } | [vatsimId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\UserSearchController::show
* @see app/Http/Controllers/UserSearchController.php:89
* @route '/users/{vatsimId}'
*/
showForm.get = (args: { vatsimId: string | number } | [vatsimId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\UserSearchController::show
* @see app/Http/Controllers/UserSearchController.php:89
* @route '/users/{vatsimId}'
*/
showForm.head = (args: { vatsimId: string | number } | [vatsimId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

const UserSearchController = { search, show }

export default UserSearchController