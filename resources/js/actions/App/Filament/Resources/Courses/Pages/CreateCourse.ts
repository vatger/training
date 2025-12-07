import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\Courses\Pages\CreateCourse::__invoke
* @see app/Filament/Resources/Courses/Pages/CreateCourse.php:7
* @route '/admin/courses/create'
*/
const CreateCourse = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: CreateCourse.url(options),
    method: 'get',
})

CreateCourse.definition = {
    methods: ["get","head"],
    url: '/admin/courses/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\Courses\Pages\CreateCourse::__invoke
* @see app/Filament/Resources/Courses/Pages/CreateCourse.php:7
* @route '/admin/courses/create'
*/
CreateCourse.url = (options?: RouteQueryOptions) => {
    return CreateCourse.definition.url + queryParams(options)
}

/**
* @see \App\Filament\Resources\Courses\Pages\CreateCourse::__invoke
* @see app/Filament/Resources/Courses/Pages/CreateCourse.php:7
* @route '/admin/courses/create'
*/
CreateCourse.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: CreateCourse.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Courses\Pages\CreateCourse::__invoke
* @see app/Filament/Resources/Courses/Pages/CreateCourse.php:7
* @route '/admin/courses/create'
*/
CreateCourse.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: CreateCourse.url(options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\Courses\Pages\CreateCourse::__invoke
* @see app/Filament/Resources/Courses/Pages/CreateCourse.php:7
* @route '/admin/courses/create'
*/
const CreateCourseForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: CreateCourse.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Courses\Pages\CreateCourse::__invoke
* @see app/Filament/Resources/Courses/Pages/CreateCourse.php:7
* @route '/admin/courses/create'
*/
CreateCourseForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: CreateCourse.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Courses\Pages\CreateCourse::__invoke
* @see app/Filament/Resources/Courses/Pages/CreateCourse.php:7
* @route '/admin/courses/create'
*/
CreateCourseForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: CreateCourse.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

CreateCourse.form = CreateCourseForm

export default CreateCourse