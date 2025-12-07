import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\Examiners\Pages\ListExaminers::__invoke
* @see app/Filament/Resources/Examiners/Pages/ListExaminers.php:7
* @route '/admin/examiners'
*/
const ListExaminers = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ListExaminers.url(options),
    method: 'get',
})

ListExaminers.definition = {
    methods: ["get","head"],
    url: '/admin/examiners',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\Examiners\Pages\ListExaminers::__invoke
* @see app/Filament/Resources/Examiners/Pages/ListExaminers.php:7
* @route '/admin/examiners'
*/
ListExaminers.url = (options?: RouteQueryOptions) => {
    return ListExaminers.definition.url + queryParams(options)
}

/**
* @see \App\Filament\Resources\Examiners\Pages\ListExaminers::__invoke
* @see app/Filament/Resources/Examiners/Pages/ListExaminers.php:7
* @route '/admin/examiners'
*/
ListExaminers.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ListExaminers.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Examiners\Pages\ListExaminers::__invoke
* @see app/Filament/Resources/Examiners/Pages/ListExaminers.php:7
* @route '/admin/examiners'
*/
ListExaminers.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ListExaminers.url(options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\Examiners\Pages\ListExaminers::__invoke
* @see app/Filament/Resources/Examiners/Pages/ListExaminers.php:7
* @route '/admin/examiners'
*/
const ListExaminersForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListExaminers.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Examiners\Pages\ListExaminers::__invoke
* @see app/Filament/Resources/Examiners/Pages/ListExaminers.php:7
* @route '/admin/examiners'
*/
ListExaminersForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListExaminers.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Examiners\Pages\ListExaminers::__invoke
* @see app/Filament/Resources/Examiners/Pages/ListExaminers.php:7
* @route '/admin/examiners'
*/
ListExaminersForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListExaminers.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

ListExaminers.form = ListExaminersForm

export default ListExaminers