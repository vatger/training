import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\WaitingListController::manage
* @see app/Http/Controllers/WaitingListController.php:26
* @route '/waiting-lists/manage'
*/
export const manage = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: manage.url(options),
    method: 'get',
})

manage.definition = {
    methods: ["get","head"],
    url: '/waiting-lists/manage',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WaitingListController::manage
* @see app/Http/Controllers/WaitingListController.php:26
* @route '/waiting-lists/manage'
*/
manage.url = (options?: RouteQueryOptions) => {
    return manage.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WaitingListController::manage
* @see app/Http/Controllers/WaitingListController.php:26
* @route '/waiting-lists/manage'
*/
manage.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: manage.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WaitingListController::manage
* @see app/Http/Controllers/WaitingListController.php:26
* @route '/waiting-lists/manage'
*/
manage.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: manage.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WaitingListController::manage
* @see app/Http/Controllers/WaitingListController.php:26
* @route '/waiting-lists/manage'
*/
const manageForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: manage.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WaitingListController::manage
* @see app/Http/Controllers/WaitingListController.php:26
* @route '/waiting-lists/manage'
*/
manageForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: manage.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WaitingListController::manage
* @see app/Http/Controllers/WaitingListController.php:26
* @route '/waiting-lists/manage'
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

const waitingLists = {
    manage: Object.assign(manage, manage),
    startTraining: Object.assign(startTraining, startTraining),
    updateRemarks: Object.assign(updateRemarks, updateRemarks),
}

export default waitingLists