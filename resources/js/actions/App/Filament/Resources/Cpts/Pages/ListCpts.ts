import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\Cpts\Pages\ListCpts::__invoke
* @see app/Filament/Resources/Cpts/Pages/ListCpts.php:7
* @route '/admin/cpts'
*/
const ListCpts = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ListCpts.url(options),
    method: 'get',
})

ListCpts.definition = {
    methods: ["get","head"],
    url: '/admin/cpts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\Cpts\Pages\ListCpts::__invoke
* @see app/Filament/Resources/Cpts/Pages/ListCpts.php:7
* @route '/admin/cpts'
*/
ListCpts.url = (options?: RouteQueryOptions) => {
    return ListCpts.definition.url + queryParams(options)
}

/**
* @see \App\Filament\Resources\Cpts\Pages\ListCpts::__invoke
* @see app/Filament/Resources/Cpts/Pages/ListCpts.php:7
* @route '/admin/cpts'
*/
ListCpts.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ListCpts.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Cpts\Pages\ListCpts::__invoke
* @see app/Filament/Resources/Cpts/Pages/ListCpts.php:7
* @route '/admin/cpts'
*/
ListCpts.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ListCpts.url(options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\Cpts\Pages\ListCpts::__invoke
* @see app/Filament/Resources/Cpts/Pages/ListCpts.php:7
* @route '/admin/cpts'
*/
const ListCptsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListCpts.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Cpts\Pages\ListCpts::__invoke
* @see app/Filament/Resources/Cpts/Pages/ListCpts.php:7
* @route '/admin/cpts'
*/
ListCptsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListCpts.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Cpts\Pages\ListCpts::__invoke
* @see app/Filament/Resources/Cpts/Pages/ListCpts.php:7
* @route '/admin/cpts'
*/
ListCptsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListCpts.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

ListCpts.form = ListCptsForm

export default ListCpts