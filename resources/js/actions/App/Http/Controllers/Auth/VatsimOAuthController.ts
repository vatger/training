import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Auth\VatsimOAuthController::redirect
* @see app/Http/Controllers/Auth/VatsimOAuthController.php:28
* @route '/auth/vatsim'
*/
export const redirect = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: redirect.url(options),
    method: 'get',
})

redirect.definition = {
    methods: ["get","head"],
    url: '/auth/vatsim',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Auth\VatsimOAuthController::redirect
* @see app/Http/Controllers/Auth/VatsimOAuthController.php:28
* @route '/auth/vatsim'
*/
redirect.url = (options?: RouteQueryOptions) => {
    return redirect.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Auth\VatsimOAuthController::redirect
* @see app/Http/Controllers/Auth/VatsimOAuthController.php:28
* @route '/auth/vatsim'
*/
redirect.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: redirect.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\VatsimOAuthController::redirect
* @see app/Http/Controllers/Auth/VatsimOAuthController.php:28
* @route '/auth/vatsim'
*/
redirect.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: redirect.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Auth\VatsimOAuthController::redirect
* @see app/Http/Controllers/Auth/VatsimOAuthController.php:28
* @route '/auth/vatsim'
*/
const redirectForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: redirect.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\VatsimOAuthController::redirect
* @see app/Http/Controllers/Auth/VatsimOAuthController.php:28
* @route '/auth/vatsim'
*/
redirectForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: redirect.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\VatsimOAuthController::redirect
* @see app/Http/Controllers/Auth/VatsimOAuthController.php:28
* @route '/auth/vatsim'
*/
redirectForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: redirect.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

redirect.form = redirectForm

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

const VatsimOAuthController = { redirect, callback }

export default VatsimOAuthController