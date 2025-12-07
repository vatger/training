import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\WaitingListController::mentorView
* @see app/Http/Controllers/WaitingListController.php:26
* @route '/waiting-lists/manage'
*/
export const mentorView = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: mentorView.url(options),
    method: 'get',
})

mentorView.definition = {
    methods: ["get","head"],
    url: '/waiting-lists/manage',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WaitingListController::mentorView
* @see app/Http/Controllers/WaitingListController.php:26
* @route '/waiting-lists/manage'
*/
mentorView.url = (options?: RouteQueryOptions) => {
    return mentorView.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WaitingListController::mentorView
* @see app/Http/Controllers/WaitingListController.php:26
* @route '/waiting-lists/manage'
*/
mentorView.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: mentorView.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WaitingListController::mentorView
* @see app/Http/Controllers/WaitingListController.php:26
* @route '/waiting-lists/manage'
*/
mentorView.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: mentorView.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WaitingListController::mentorView
* @see app/Http/Controllers/WaitingListController.php:26
* @route '/waiting-lists/manage'
*/
const mentorViewForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: mentorView.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WaitingListController::mentorView
* @see app/Http/Controllers/WaitingListController.php:26
* @route '/waiting-lists/manage'
*/
mentorViewForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: mentorView.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WaitingListController::mentorView
* @see app/Http/Controllers/WaitingListController.php:26
* @route '/waiting-lists/manage'
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
* @see \App\Http\Controllers\WaitingListController::startTraining
* @see app/Http/Controllers/WaitingListController.php:130
* @route '/waiting-lists/{entry}/start-training'
*/
export const startTraining = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: startTraining.url(args, options),
    method: 'post',
})

startTraining.definition = {
    methods: ["post"],
    url: '/waiting-lists/{entry}/start-training',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\WaitingListController::startTraining
* @see app/Http/Controllers/WaitingListController.php:130
* @route '/waiting-lists/{entry}/start-training'
*/
startTraining.url = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { entry: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { entry: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            entry: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        entry: typeof args.entry === 'object'
        ? args.entry.id
        : args.entry,
    }

    return startTraining.definition.url
            .replace('{entry}', parsedArgs.entry.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\WaitingListController::startTraining
* @see app/Http/Controllers/WaitingListController.php:130
* @route '/waiting-lists/{entry}/start-training'
*/
startTraining.post = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: startTraining.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WaitingListController::startTraining
* @see app/Http/Controllers/WaitingListController.php:130
* @route '/waiting-lists/{entry}/start-training'
*/
const startTrainingForm = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: startTraining.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WaitingListController::startTraining
* @see app/Http/Controllers/WaitingListController.php:130
* @route '/waiting-lists/{entry}/start-training'
*/
startTrainingForm.post = (args: { entry: number | { id: number } } | [entry: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: startTraining.url(args, options),
    method: 'post',
})

startTraining.form = startTrainingForm

/**
* @see \App\Http\Controllers\WaitingListController::updateRemarks
* @see app/Http/Controllers/WaitingListController.php:166
* @route '/waiting-lists/update-remarks'
*/
export const updateRemarks = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateRemarks.url(options),
    method: 'post',
})

updateRemarks.definition = {
    methods: ["post"],
    url: '/waiting-lists/update-remarks',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\WaitingListController::updateRemarks
* @see app/Http/Controllers/WaitingListController.php:166
* @route '/waiting-lists/update-remarks'
*/
updateRemarks.url = (options?: RouteQueryOptions) => {
    return updateRemarks.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WaitingListController::updateRemarks
* @see app/Http/Controllers/WaitingListController.php:166
* @route '/waiting-lists/update-remarks'
*/
updateRemarks.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateRemarks.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WaitingListController::updateRemarks
* @see app/Http/Controllers/WaitingListController.php:166
* @route '/waiting-lists/update-remarks'
*/
const updateRemarksForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateRemarks.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\WaitingListController::updateRemarks
* @see app/Http/Controllers/WaitingListController.php:166
* @route '/waiting-lists/update-remarks'
*/
updateRemarksForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateRemarks.url(options),
    method: 'post',
})

updateRemarks.form = updateRemarksForm

const WaitingListController = { mentorView, startTraining, updateRemarks }

export default WaitingListController