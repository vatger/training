import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\EndorsementController::traineeView
* @see app/Http/Controllers/EndorsementController.php:30
* @route '/endorsements/my-endorsements'
*/
export const traineeView = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: traineeView.url(options),
    method: 'get',
})

traineeView.definition = {
    methods: ["get","head"],
    url: '/endorsements/my-endorsements',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EndorsementController::traineeView
* @see app/Http/Controllers/EndorsementController.php:30
* @route '/endorsements/my-endorsements'
*/
traineeView.url = (options?: RouteQueryOptions) => {
    return traineeView.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EndorsementController::traineeView
* @see app/Http/Controllers/EndorsementController.php:30
* @route '/endorsements/my-endorsements'
*/
traineeView.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: traineeView.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EndorsementController::traineeView
* @see app/Http/Controllers/EndorsementController.php:30
* @route '/endorsements/my-endorsements'
*/
traineeView.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: traineeView.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EndorsementController::traineeView
* @see app/Http/Controllers/EndorsementController.php:30
* @route '/endorsements/my-endorsements'
*/
const traineeViewForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: traineeView.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EndorsementController::traineeView
* @see app/Http/Controllers/EndorsementController.php:30
* @route '/endorsements/my-endorsements'
*/
traineeViewForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: traineeView.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EndorsementController::traineeView
* @see app/Http/Controllers/EndorsementController.php:30
* @route '/endorsements/my-endorsements'
*/
traineeViewForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: traineeView.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

traineeView.form = traineeViewForm

/**
* @see \App\Http\Controllers\EndorsementController::mentorView
* @see app/Http/Controllers/EndorsementController.php:71
* @route '/endorsements/manage'
*/
export const mentorView = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: mentorView.url(options),
    method: 'get',
})

mentorView.definition = {
    methods: ["get","head"],
    url: '/endorsements/manage',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EndorsementController::mentorView
* @see app/Http/Controllers/EndorsementController.php:71
* @route '/endorsements/manage'
*/
mentorView.url = (options?: RouteQueryOptions) => {
    return mentorView.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EndorsementController::mentorView
* @see app/Http/Controllers/EndorsementController.php:71
* @route '/endorsements/manage'
*/
mentorView.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: mentorView.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EndorsementController::mentorView
* @see app/Http/Controllers/EndorsementController.php:71
* @route '/endorsements/manage'
*/
mentorView.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: mentorView.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EndorsementController::mentorView
* @see app/Http/Controllers/EndorsementController.php:71
* @route '/endorsements/manage'
*/
const mentorViewForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: mentorView.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EndorsementController::mentorView
* @see app/Http/Controllers/EndorsementController.php:71
* @route '/endorsements/manage'
*/
mentorViewForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: mentorView.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EndorsementController::mentorView
* @see app/Http/Controllers/EndorsementController.php:71
* @route '/endorsements/manage'
*/
mentorViewForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: mentorView.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

mentorView.form = mentorViewForm

/**
* @see \App\Http\Controllers\EndorsementController::removeTier1
* @see app/Http/Controllers/EndorsementController.php:174
* @route '/endorsements/tier1/{endorsementId}/remove'
*/
export const removeTier1 = (args: { endorsementId: string | number } | [endorsementId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: removeTier1.url(args, options),
    method: 'delete',
})

removeTier1.definition = {
    methods: ["delete"],
    url: '/endorsements/tier1/{endorsementId}/remove',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\EndorsementController::removeTier1
* @see app/Http/Controllers/EndorsementController.php:174
* @route '/endorsements/tier1/{endorsementId}/remove'
*/
removeTier1.url = (args: { endorsementId: string | number } | [endorsementId: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return removeTier1.definition.url
            .replace('{endorsementId}', parsedArgs.endorsementId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EndorsementController::removeTier1
* @see app/Http/Controllers/EndorsementController.php:174
* @route '/endorsements/tier1/{endorsementId}/remove'
*/
removeTier1.delete = (args: { endorsementId: string | number } | [endorsementId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: removeTier1.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\EndorsementController::removeTier1
* @see app/Http/Controllers/EndorsementController.php:174
* @route '/endorsements/tier1/{endorsementId}/remove'
*/
const removeTier1Form = (args: { endorsementId: string | number } | [endorsementId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: removeTier1.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EndorsementController::removeTier1
* @see app/Http/Controllers/EndorsementController.php:174
* @route '/endorsements/tier1/{endorsementId}/remove'
*/
removeTier1Form.delete = (args: { endorsementId: string | number } | [endorsementId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: removeTier1.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

removeTier1.form = removeTier1Form

/**
* @see \App\Http\Controllers\EndorsementController::requestTier2
* @see app/Http/Controllers/EndorsementController.php:247
* @route '/endorsements/tier2/{tier2Id}/request'
*/
export const requestTier2 = (args: { tier2Id: string | number } | [tier2Id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: requestTier2.url(args, options),
    method: 'post',
})

requestTier2.definition = {
    methods: ["post"],
    url: '/endorsements/tier2/{tier2Id}/request',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EndorsementController::requestTier2
* @see app/Http/Controllers/EndorsementController.php:247
* @route '/endorsements/tier2/{tier2Id}/request'
*/
requestTier2.url = (args: { tier2Id: string | number } | [tier2Id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return requestTier2.definition.url
            .replace('{tier2Id}', parsedArgs.tier2Id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EndorsementController::requestTier2
* @see app/Http/Controllers/EndorsementController.php:247
* @route '/endorsements/tier2/{tier2Id}/request'
*/
requestTier2.post = (args: { tier2Id: string | number } | [tier2Id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: requestTier2.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EndorsementController::requestTier2
* @see app/Http/Controllers/EndorsementController.php:247
* @route '/endorsements/tier2/{tier2Id}/request'
*/
const requestTier2Form = (args: { tier2Id: string | number } | [tier2Id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: requestTier2.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EndorsementController::requestTier2
* @see app/Http/Controllers/EndorsementController.php:247
* @route '/endorsements/tier2/{tier2Id}/request'
*/
requestTier2Form.post = (args: { tier2Id: string | number } | [tier2Id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: requestTier2.url(args, options),
    method: 'post',
})

requestTier2.form = requestTier2Form

const EndorsementController = { traineeView, mentorView, removeTier1, requestTier2 }

export default EndorsementController