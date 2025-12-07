import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\EndorsementController::remove
* @see app/Http/Controllers/EndorsementController.php:174
* @route '/endorsements/tier1/{endorsementId}/remove'
*/
export const remove = (args: { endorsementId: string | number } | [endorsementId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: remove.url(args, options),
    method: 'delete',
})

remove.definition = {
    methods: ["delete"],
    url: '/endorsements/tier1/{endorsementId}/remove',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\EndorsementController::remove
* @see app/Http/Controllers/EndorsementController.php:174
* @route '/endorsements/tier1/{endorsementId}/remove'
*/
remove.url = (args: { endorsementId: string | number } | [endorsementId: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { endorsementId: args }
    }

    if (Array.isArray(args)) {
        args = {
            endorsementId: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        endorsementId: args.endorsementId,
    }

    return remove.definition.url
            .replace('{endorsementId}', parsedArgs.endorsementId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EndorsementController::remove
* @see app/Http/Controllers/EndorsementController.php:174
* @route '/endorsements/tier1/{endorsementId}/remove'
*/
remove.delete = (args: { endorsementId: string | number } | [endorsementId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: remove.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\EndorsementController::remove
* @see app/Http/Controllers/EndorsementController.php:174
* @route '/endorsements/tier1/{endorsementId}/remove'
*/
const removeForm = (args: { endorsementId: string | number } | [endorsementId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: remove.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EndorsementController::remove
* @see app/Http/Controllers/EndorsementController.php:174
* @route '/endorsements/tier1/{endorsementId}/remove'
*/
removeForm.delete = (args: { endorsementId: string | number } | [endorsementId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: remove.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

remove.form = removeForm

const tier1 = {
    remove: Object.assign(remove, remove),
}

export default tier1