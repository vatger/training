import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\Familiarisations\Pages\ListFamiliarisations::__invoke
* @see app/Filament/Resources/Familiarisations/Pages/ListFamiliarisations.php:7
* @route '/admin/familiarisations'
*/
const ListFamiliarisations = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ListFamiliarisations.url(options),
    method: 'get',
})

ListFamiliarisations.definition = {
    methods: ["get","head"],
    url: '/admin/familiarisations',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\Familiarisations\Pages\ListFamiliarisations::__invoke
* @see app/Filament/Resources/Familiarisations/Pages/ListFamiliarisations.php:7
* @route '/admin/familiarisations'
*/
ListFamiliarisations.url = (options?: RouteQueryOptions) => {
    return ListFamiliarisations.definition.url + queryParams(options)
}

/**
* @see \App\Filament\Resources\Familiarisations\Pages\ListFamiliarisations::__invoke
* @see app/Filament/Resources/Familiarisations/Pages/ListFamiliarisations.php:7
* @route '/admin/familiarisations'
*/
ListFamiliarisations.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ListFamiliarisations.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Familiarisations\Pages\ListFamiliarisations::__invoke
* @see app/Filament/Resources/Familiarisations/Pages/ListFamiliarisations.php:7
* @route '/admin/familiarisations'
*/
ListFamiliarisations.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ListFamiliarisations.url(options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\Familiarisations\Pages\ListFamiliarisations::__invoke
* @see app/Filament/Resources/Familiarisations/Pages/ListFamiliarisations.php:7
* @route '/admin/familiarisations'
*/
const ListFamiliarisationsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListFamiliarisations.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Familiarisations\Pages\ListFamiliarisations::__invoke
* @see app/Filament/Resources/Familiarisations/Pages/ListFamiliarisations.php:7
* @route '/admin/familiarisations'
*/
ListFamiliarisationsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListFamiliarisations.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Familiarisations\Pages\ListFamiliarisations::__invoke
* @see app/Filament/Resources/Familiarisations/Pages/ListFamiliarisations.php:7
* @route '/admin/familiarisations'
*/
ListFamiliarisationsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListFamiliarisations.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

ListFamiliarisations.form = ListFamiliarisationsForm

export default ListFamiliarisations