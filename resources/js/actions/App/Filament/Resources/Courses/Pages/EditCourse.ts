import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\Courses\Pages\EditCourse::__invoke
* @see app/Filament/Resources/Courses/Pages/EditCourse.php:7
* @route '/admin/courses/{record}/edit'
*/
const EditCourse = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: EditCourse.url(args, options),
    method: 'get',
})

EditCourse.definition = {
    methods: ["get","head"],
    url: '/admin/courses/{record}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\Courses\Pages\EditCourse::__invoke
* @see app/Filament/Resources/Courses/Pages/EditCourse.php:7
* @route '/admin/courses/{record}/edit'
*/
EditCourse.url = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { record: args }
    }

    if (Array.isArray(args)) {
        args = {
            record: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        record: args.record,
    }

    return EditCourse.definition.url
            .replace('{record}', parsedArgs.record.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Filament\Resources\Courses\Pages\EditCourse::__invoke
* @see app/Filament/Resources/Courses/Pages/EditCourse.php:7
* @route '/admin/courses/{record}/edit'
*/
EditCourse.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: EditCourse.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Courses\Pages\EditCourse::__invoke
* @see app/Filament/Resources/Courses/Pages/EditCourse.php:7
* @route '/admin/courses/{record}/edit'
*/
EditCourse.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: EditCourse.url(args, options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\Courses\Pages\EditCourse::__invoke
* @see app/Filament/Resources/Courses/Pages/EditCourse.php:7
* @route '/admin/courses/{record}/edit'
*/
const EditCourseForm = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EditCourse.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Courses\Pages\EditCourse::__invoke
* @see app/Filament/Resources/Courses/Pages/EditCourse.php:7
* @route '/admin/courses/{record}/edit'
*/
EditCourseForm.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EditCourse.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Courses\Pages\EditCourse::__invoke
* @see app/Filament/Resources/Courses/Pages/EditCourse.php:7
* @route '/admin/courses/{record}/edit'
*/
EditCourseForm.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EditCourse.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

EditCourse.form = EditCourseForm

export default EditCourse