import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import tier1 from './tier1'
import tier2 from './tier2'
/**
* @see \App\Http\Controllers\EndorsementController::manage
* @see app/Http/Controllers/EndorsementController.php:71
* @route '/endorsements/manage'
*/
export const manage = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: manage.url(options),
    method: 'get',
})

manage.definition = {
    methods: ["get","head"],
    url: '/endorsements/manage',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EndorsementController::manage
* @see app/Http/Controllers/EndorsementController.php:71
* @route '/endorsements/manage'
*/
manage.url = (options?: RouteQueryOptions) => {
    return manage.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EndorsementController::manage
* @see app/Http/Controllers/EndorsementController.php:71
* @route '/endorsements/manage'
*/
manage.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: manage.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EndorsementController::manage
* @see app/Http/Controllers/EndorsementController.php:71
* @route '/endorsements/manage'
*/
manage.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: manage.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EndorsementController::manage
* @see app/Http/Controllers/EndorsementController.php:71
* @route '/endorsements/manage'
*/
const manageForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: manage.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EndorsementController::manage
* @see app/Http/Controllers/EndorsementController.php:71
* @route '/endorsements/manage'
*/
manageForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: manage.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EndorsementController::manage
* @see app/Http/Controllers/EndorsementController.php:71
* @route '/endorsements/manage'
*/
manageForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: manage.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

manage.form = manageForm

const endorsements = {
    manage: Object.assign(manage, manage),
    tier1: Object.assign(tier1, tier1),
    tier2: Object.assign(tier2, tier2),
}

export default endorsements