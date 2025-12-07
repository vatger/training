import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\MentorOverviewController::trainees
* @see app/Http/Controllers/MentorOverviewController.php:122
* @route '/overview/mentor-overview/course/{courseId}/trainees'
*/
export const trainees = (args: { courseId: string | number } | [courseId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: trainees.url(args, options),
    method: 'get',
})

trainees.definition = {
    methods: ["get","head"],
    url: '/overview/mentor-overview/course/{courseId}/trainees',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MentorOverviewController::trainees
* @see app/Http/Controllers/MentorOverviewController.php:122
* @route '/overview/mentor-overview/course/{courseId}/trainees'
*/
trainees.url = (args: { courseId: string | number } | [courseId: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { courseId: args }
    }

    if (Array.isArray(args)) {
        args = {
            courseId: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        courseId: args.courseId,
    }

    return trainees.definition.url
            .replace('{courseId}', parsedArgs.courseId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MentorOverviewController::trainees
* @see app/Http/Controllers/MentorOverviewController.php:122
* @route '/overview/mentor-overview/course/{courseId}/trainees'
*/
trainees.get = (args: { courseId: string | number } | [courseId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: trainees.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::trainees
* @see app/Http/Controllers/MentorOverviewController.php:122
* @route '/overview/mentor-overview/course/{courseId}/trainees'
*/
trainees.head = (args: { courseId: string | number } | [courseId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: trainees.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::trainees
* @see app/Http/Controllers/MentorOverviewController.php:122
* @route '/overview/mentor-overview/course/{courseId}/trainees'
*/
const traineesForm = (args: { courseId: string | number } | [courseId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: trainees.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::trainees
* @see app/Http/Controllers/MentorOverviewController.php:122
* @route '/overview/mentor-overview/course/{courseId}/trainees'
*/
traineesForm.get = (args: { courseId: string | number } | [courseId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: trainees.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::trainees
* @see app/Http/Controllers/MentorOverviewController.php:122
* @route '/overview/mentor-overview/course/{courseId}/trainees'
*/
traineesForm.head = (args: { courseId: string | number } | [courseId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: trainees.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

trainees.form = traineesForm

const course = {
    trainees: Object.assign(trainees, trainees),
}

export default course