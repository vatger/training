import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
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
* @see \App\Http\Controllers\FamiliarisationController::userFamiliarisations
* @see app/Http/Controllers/FamiliarisationController.php:71
* @route '/familiarisations/my-familiarisations'
*/
export const userFamiliarisations = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: userFamiliarisations.url(options),
    method: 'get',
})

userFamiliarisations.definition = {
    methods: ["get","head"],
    url: '/familiarisations/my-familiarisations',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\FamiliarisationController::userFamiliarisations
* @see app/Http/Controllers/FamiliarisationController.php:71
* @route '/familiarisations/my-familiarisations'
*/
userFamiliarisations.url = (options?: RouteQueryOptions) => {
    return userFamiliarisations.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\FamiliarisationController::userFamiliarisations
* @see app/Http/Controllers/FamiliarisationController.php:71
* @route '/familiarisations/my-familiarisations'
*/
userFamiliarisations.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: userFamiliarisations.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\FamiliarisationController::userFamiliarisations
* @see app/Http/Controllers/FamiliarisationController.php:71
* @route '/familiarisations/my-familiarisations'
*/
userFamiliarisations.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: userFamiliarisations.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\FamiliarisationController::userFamiliarisations
* @see app/Http/Controllers/FamiliarisationController.php:71
* @route '/familiarisations/my-familiarisations'
*/
const userFamiliarisationsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: userFamiliarisations.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\FamiliarisationController::userFamiliarisations
* @see app/Http/Controllers/FamiliarisationController.php:71
* @route '/familiarisations/my-familiarisations'
*/
userFamiliarisationsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: userFamiliarisations.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\FamiliarisationController::userFamiliarisations
* @see app/Http/Controllers/FamiliarisationController.php:71
* @route '/familiarisations/my-familiarisations'
*/
userFamiliarisationsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: userFamiliarisations.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

userFamiliarisations.form = userFamiliarisationsForm

const FamiliarisationController = { index, userFamiliarisations }

export default FamiliarisationController