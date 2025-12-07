import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\FamiliarisationController::index
* @see app/Http/Controllers/FamiliarisationController.php:22
* @route '/familiarisations'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/familiarisations',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\FamiliarisationController::index
* @see app/Http/Controllers/FamiliarisationController.php:22
* @route '/familiarisations'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\FamiliarisationController::index
* @see app/Http/Controllers/FamiliarisationController.php:22
* @route '/familiarisations'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\FamiliarisationController::index
* @see app/Http/Controllers/FamiliarisationController.php:22
* @route '/familiarisations'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\FamiliarisationController::index
* @see app/Http/Controllers/FamiliarisationController.php:22
* @route '/familiarisations'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\FamiliarisationController::index
* @see app/Http/Controllers/FamiliarisationController.php:22
* @route '/familiarisations'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\FamiliarisationController::index
* @see app/Http/Controllers/FamiliarisationController.php:22
* @route '/familiarisations'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\FamiliarisationController::user
* @see app/Http/Controllers/FamiliarisationController.php:71
* @route '/familiarisations/my-familiarisations'
*/
export const user = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: user.url(options),
    method: 'get',
})

user.definition = {
    methods: ["get","head"],
    url: '/familiarisations/my-familiarisations',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\FamiliarisationController::user
* @see app/Http/Controllers/FamiliarisationController.php:71
* @route '/familiarisations/my-familiarisations'
*/
user.url = (options?: RouteQueryOptions) => {
    return user.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\FamiliarisationController::user
* @see app/Http/Controllers/FamiliarisationController.php:71
* @route '/familiarisations/my-familiarisations'
*/
user.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: user.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\FamiliarisationController::user
* @see app/Http/Controllers/FamiliarisationController.php:71
* @route '/familiarisations/my-familiarisations'
*/
user.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: user.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\FamiliarisationController::user
* @see app/Http/Controllers/FamiliarisationController.php:71
* @route '/familiarisations/my-familiarisations'
*/
const userForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: user.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\FamiliarisationController::user
* @see app/Http/Controllers/FamiliarisationController.php:71
* @route '/familiarisations/my-familiarisations'
*/
userForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: user.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\FamiliarisationController::user
* @see app/Http/Controllers/FamiliarisationController.php:71
* @route '/familiarisations/my-familiarisations'
*/
userForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: user.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

user.form = userForm

const familiarisations = {
    index: Object.assign(index, index),
    user: Object.assign(user, user),
}

export default familiarisations