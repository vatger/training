import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\TrainingLogController::course
* @see app/Http/Controllers/TrainingLogController.php:514
* @route '/api/training-logs/course/{courseId}'
*/
export const course = (args: { courseId: string | number } | [courseId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: course.url(args, options),
    method: 'get',
})

course.definition = {
    methods: ["get","head"],
    url: '/api/training-logs/course/{courseId}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TrainingLogController::course
* @see app/Http/Controllers/TrainingLogController.php:514
* @route '/api/training-logs/course/{courseId}'
*/
course.url = (args: { courseId: string | number } | [courseId: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return course.definition.url
            .replace('{courseId}', parsedArgs.courseId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TrainingLogController::course
* @see app/Http/Controllers/TrainingLogController.php:514
* @route '/api/training-logs/course/{courseId}'
*/
course.get = (args: { courseId: string | number } | [courseId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: course.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TrainingLogController::course
* @see app/Http/Controllers/TrainingLogController.php:514
* @route '/api/training-logs/course/{courseId}'
*/
course.head = (args: { courseId: string | number } | [courseId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: course.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TrainingLogController::course
* @see app/Http/Controllers/TrainingLogController.php:514
* @route '/api/training-logs/course/{courseId}'
*/
const courseForm = (args: { courseId: string | number } | [courseId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: course.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TrainingLogController::course
* @see app/Http/Controllers/TrainingLogController.php:514
* @route '/api/training-logs/course/{courseId}'
*/
courseForm.get = (args: { courseId: string | number } | [courseId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: course.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TrainingLogController::course
* @see app/Http/Controllers/TrainingLogController.php:514
* @route '/api/training-logs/course/{courseId}'
*/
courseForm.head = (args: { courseId: string | number } | [courseId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: course.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

course.form = courseForm

/**
* @see \App\Http\Controllers\TrainingLogController::trainee
* @see app/Http/Controllers/TrainingLogController.php:478
* @route '/api/training-logs/trainee/{traineeId}'
*/
export const trainee = (args: { traineeId: string | number } | [traineeId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: trainee.url(args, options),
    method: 'get',
})

trainee.definition = {
    methods: ["get","head"],
    url: '/api/training-logs/trainee/{traineeId}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TrainingLogController::trainee
* @see app/Http/Controllers/TrainingLogController.php:478
* @route '/api/training-logs/trainee/{traineeId}'
*/
trainee.url = (args: { traineeId: string | number } | [traineeId: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { traineeId: args }
    }

    if (Array.isArray(args)) {
        args = {
            traineeId: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        traineeId: args.traineeId,
    }

    return trainee.definition.url
            .replace('{traineeId}', parsedArgs.traineeId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TrainingLogController::trainee
* @see app/Http/Controllers/TrainingLogController.php:478
* @route '/api/training-logs/trainee/{traineeId}'
*/
trainee.get = (args: { traineeId: string | number } | [traineeId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: trainee.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TrainingLogController::trainee
* @see app/Http/Controllers/TrainingLogController.php:478
* @route '/api/training-logs/trainee/{traineeId}'
*/
trainee.head = (args: { traineeId: string | number } | [traineeId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: trainee.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TrainingLogController::trainee
* @see app/Http/Controllers/TrainingLogController.php:478
* @route '/api/training-logs/trainee/{traineeId}'
*/
const traineeForm = (args: { traineeId: string | number } | [traineeId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: trainee.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TrainingLogController::trainee
* @see app/Http/Controllers/TrainingLogController.php:478
* @route '/api/training-logs/trainee/{traineeId}'
*/
traineeForm.get = (args: { traineeId: string | number } | [traineeId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: trainee.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TrainingLogController::trainee
* @see app/Http/Controllers/TrainingLogController.php:478
* @route '/api/training-logs/trainee/{traineeId}'
*/
traineeForm.head = (args: { traineeId: string | number } | [traineeId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: trainee.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

trainee.form = traineeForm

const trainingLogs = {
    course: Object.assign(course, course),
    trainee: Object.assign(trainee, trainee),
}

export default trainingLogs