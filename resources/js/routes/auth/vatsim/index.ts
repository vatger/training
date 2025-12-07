import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Auth\VatsimOAuthController::callback
* @see app/Http/Controllers/Auth/VatsimOAuthController.php:44
* @route '/auth/vatsim/callback'
*/
export const callback = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: callback.url(options),
    method: 'get',
})

callback.definition = {
    methods: ["get","head"],
    url: '/auth/vatsim/callback',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Auth\VatsimOAuthController::callback
* @see app/Http/Controllers/Auth/VatsimOAuthController.php:44
* @route '/auth/vatsim/callback'
*/
callback.url = (options?: RouteQueryOptions) => {
    return callback.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Auth\VatsimOAuthController::callback
* @see app/Http/Controllers/Auth/VatsimOAuthController.php:44
* @route '/auth/vatsim/callback'
*/
callback.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: callback.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\VatsimOAuthController::callback
* @see app/Http/Controllers/Auth/VatsimOAuthController.php:44
* @route '/auth/vatsim/callback'
*/
callback.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: callback.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Auth\VatsimOAuthController::callback
* @see app/Http/Controllers/Auth/VatsimOAuthController.php:44
* @route '/auth/vatsim/callback'
*/
const callbackForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: callback.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\VatsimOAuthController::callback
* @see app/Http/Controllers/Auth/VatsimOAuthController.php:44
* @route '/auth/vatsim/callback'
*/
callbackForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: callback.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\VatsimOAuthController::callback
* @see app/Http/Controllers/Auth/VatsimOAuthController.php:44
* @route '/auth/vatsim/callback'
*/
callbackForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: callback.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

callback.form = callbackForm

const vatsim = {
    callback: Object.assign(callback, callback),
}

export default vatsim