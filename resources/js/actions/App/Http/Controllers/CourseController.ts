import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\CourseController::index
* @see app/Http/Controllers/CourseController.php:36
* @route '/courses'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/courses',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CourseController::index
* @see app/Http/Controllers/CourseController.php:36
* @route '/courses'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CourseController::index
* @see app/Http/Controllers/CourseController.php:36
* @route '/courses'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CourseController::index
* @see app/Http/Controllers/CourseController.php:36
* @route '/courses'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CourseController::index
* @see app/Http/Controllers/CourseController.php:36
* @route '/courses'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CourseController::index
* @see app/Http/Controllers/CourseController.php:36
* @route '/courses'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CourseController::index
* @see app/Http/Controllers/CourseController.php:36
* @route '/courses'
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
* @see \App\Http\Controllers\CourseController::toggleWaitingList
* @see app/Http/Controllers/CourseController.php:128
* @route '/courses/{course}/waiting-list'
*/
export const toggleWaitingList = (args: { course: number | { id: number } } | [course: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: toggleWaitingList.url(args, options),
    method: 'post',
})

toggleWaitingList.definition = {
    methods: ["post"],
    url: '/courses/{course}/waiting-list',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CourseController::toggleWaitingList
* @see app/Http/Controllers/CourseController.php:128
* @route '/courses/{course}/waiting-list'
*/
toggleWaitingList.url = (args: { course: number | { id: number } } | [course: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { course: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { course: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            course: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        course: typeof args.course === 'object'
        ? args.course.id
        : args.course,
    }

    return toggleWaitingList.definition.url
            .replace('{course}', parsedArgs.course.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CourseController::toggleWaitingList
* @see app/Http/Controllers/CourseController.php:128
* @route '/courses/{course}/waiting-list'
*/
toggleWaitingList.post = (args: { course: number | { id: number } } | [course: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: toggleWaitingList.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CourseController::toggleWaitingList
* @see app/Http/Controllers/CourseController.php:128
* @route '/courses/{course}/waiting-list'
*/
const toggleWaitingListForm = (args: { course: number | { id: number } } | [course: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: toggleWaitingList.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CourseController::toggleWaitingList
* @see app/Http/Controllers/CourseController.php:128
* @route '/courses/{course}/waiting-list'
*/
toggleWaitingListForm.post = (args: { course: number | { id: number } } | [course: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: toggleWaitingList.url(args, options),
    method: 'post',
})

toggleWaitingList.form = toggleWaitingListForm

const CourseController = { index, toggleWaitingList }

export default CourseController