import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import vatsim1dcbe9 from './vatsim'
/**
* @see \App\Http\Controllers\Auth\VatsimOAuthController::vatsim
* @see app/Http/Controllers/Auth/VatsimOAuthController.php:28
* @route '/auth/vatsim'
*/
export const vatsim = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: vatsim.url(options),
    method: 'get',
})

vatsim.definition = {
    methods: ["get","head"],
    url: '/auth/vatsim',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Auth\VatsimOAuthController::vatsim
* @see app/Http/Controllers/Auth/VatsimOAuthController.php:28
* @route '/auth/vatsim'
*/
vatsim.url = (options?: RouteQueryOptions) => {
    return vatsim.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Auth\VatsimOAuthController::vatsim
* @see app/Http/Controllers/Auth/VatsimOAuthController.php:28
* @route '/auth/vatsim'
*/
vatsim.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: vatsim.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\VatsimOAuthController::vatsim
* @see app/Http/Controllers/Auth/VatsimOAuthController.php:28
* @route '/auth/vatsim'
*/
vatsim.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: vatsim.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Auth\VatsimOAuthController::vatsim
* @see app/Http/Controllers/Auth/VatsimOAuthController.php:28
* @route '/auth/vatsim'
*/
const vatsimForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: vatsim.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\VatsimOAuthController::vatsim
* @see app/Http/Controllers/Auth/VatsimOAuthController.php:28
* @route '/auth/vatsim'
*/
vatsimForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: vatsim.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\VatsimOAuthController::vatsim
* @see app/Http/Controllers/Auth/VatsimOAuthController.php:28
* @route '/auth/vatsim'
*/
vatsimForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: vatsim.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

vatsim.form = vatsimForm

const auth = {
    vatsim: Object.assign(vatsim, vatsim1dcbe9),
}

export default auth