import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\Courses\Pages\ListCourses::__invoke
* @see app/Filament/Resources/Courses/Pages/ListCourses.php:7
* @route '/admin/courses'
*/
const ListCourses = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ListCourses.url(options),
    method: 'get',
})

ListCourses.definition = {
    methods: ["get","head"],
    url: '/admin/courses',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\Courses\Pages\ListCourses::__invoke
* @see app/Filament/Resources/Courses/Pages/ListCourses.php:7
* @route '/admin/courses'
*/
ListCourses.url = (options?: RouteQueryOptions) => {
    return ListCourses.definition.url + queryParams(options)
}

/**
* @see \App\Filament\Resources\Courses\Pages\ListCourses::__invoke
* @see app/Filament/Resources/Courses/Pages/ListCourses.php:7
* @route '/admin/courses'
*/
ListCourses.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ListCourses.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Courses\Pages\ListCourses::__invoke
* @see app/Filament/Resources/Courses/Pages/ListCourses.php:7
* @route '/admin/courses'
*/
ListCourses.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ListCourses.url(options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\Courses\Pages\ListCourses::__invoke
* @see app/Filament/Resources/Courses/Pages/ListCourses.php:7
* @route '/admin/courses'
*/
const ListCoursesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListCourses.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Courses\Pages\ListCourses::__invoke
* @see app/Filament/Resources/Courses/Pages/ListCourses.php:7
* @route '/admin/courses'
*/
ListCoursesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListCourses.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Courses\Pages\ListCourses::__invoke
* @see app/Filament/Resources/Courses/Pages/ListCourses.php:7
* @route '/admin/courses'
*/
ListCoursesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListCourses.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

ListCourses.form = ListCoursesForm

export default ListCourses