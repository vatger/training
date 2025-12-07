import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\EndorsementController::request
* @see app/Http/Controllers/EndorsementController.php:247
* @route '/endorsements/tier2/{tier2Id}/request'
*/
export const request = (args: { tier2Id: string | number } | [tier2Id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: request.url(args, options),
    method: 'post',
})

request.definition = {
    methods: ["post"],
    url: '/endorsements/tier2/{tier2Id}/request',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EndorsementController::request
* @see app/Http/Controllers/EndorsementController.php:247
* @route '/endorsements/tier2/{tier2Id}/request'
*/
request.url = (args: { tier2Id: string | number } | [tier2Id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { tier2Id: args }
    }

    if (Array.isArray(args)) {
        args = {
            tier2Id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        tier2Id: args.tier2Id,
    }

    return request.definition.url
            .replace('{tier2Id}', parsedArgs.tier2Id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EndorsementController::request
* @see app/Http/Controllers/EndorsementController.php:247
* @route '/endorsements/tier2/{tier2Id}/request'
*/
request.post = (args: { tier2Id: string | number } | [tier2Id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: request.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EndorsementController::request
* @see app/Http/Controllers/EndorsementController.php:247
* @route '/endorsements/tier2/{tier2Id}/request'
*/
const requestForm = (args: { tier2Id: string | number } | [tier2Id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: request.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EndorsementController::request
* @see app/Http/Controllers/EndorsementController.php:247
* @route '/endorsements/tier2/{tier2Id}/request'
*/
requestForm.post = (args: { tier2Id: string | number } | [tier2Id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: request.url(args, options),
    method: 'post',
})

request.form = requestForm

const tier2 = {
    request: Object.assign(request, request),
}

export default tier2