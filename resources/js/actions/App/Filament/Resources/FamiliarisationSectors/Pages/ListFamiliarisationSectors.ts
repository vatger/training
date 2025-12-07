import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\FamiliarisationSectors\Pages\ListFamiliarisationSectors::__invoke
* @see app/Filament/Resources/FamiliarisationSectors/Pages/ListFamiliarisationSectors.php:7
* @route '/admin/familiarisation-sectors'
*/
const ListFamiliarisationSectors = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ListFamiliarisationSectors.url(options),
    method: 'get',
})

ListFamiliarisationSectors.definition = {
    methods: ["get","head"],
    url: '/admin/familiarisation-sectors',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\FamiliarisationSectors\Pages\ListFamiliarisationSectors::__invoke
* @see app/Filament/Resources/FamiliarisationSectors/Pages/ListFamiliarisationSectors.php:7
* @route '/admin/familiarisation-sectors'
*/
ListFamiliarisationSectors.url = (options?: RouteQueryOptions) => {
    return ListFamiliarisationSectors.definition.url + queryParams(options)
}

/**
* @see \App\Filament\Resources\FamiliarisationSectors\Pages\ListFamiliarisationSectors::__invoke
* @see app/Filament/Resources/FamiliarisationSectors/Pages/ListFamiliarisationSectors.php:7
* @route '/admin/familiarisation-sectors'
*/
ListFamiliarisationSectors.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ListFamiliarisationSectors.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\FamiliarisationSectors\Pages\ListFamiliarisationSectors::__invoke
* @see app/Filament/Resources/FamiliarisationSectors/Pages/ListFamiliarisationSectors.php:7
* @route '/admin/familiarisation-sectors'
*/
ListFamiliarisationSectors.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ListFamiliarisationSectors.url(options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\FamiliarisationSectors\Pages\ListFamiliarisationSectors::__invoke
* @see app/Filament/Resources/FamiliarisationSectors/Pages/ListFamiliarisationSectors.php:7
* @route '/admin/familiarisation-sectors'
*/
const ListFamiliarisationSectorsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListFamiliarisationSectors.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\FamiliarisationSectors\Pages\ListFamiliarisationSectors::__invoke
* @see app/Filament/Resources/FamiliarisationSectors/Pages/ListFamiliarisationSectors.php:7
* @route '/admin/familiarisation-sectors'
*/
ListFamiliarisationSectorsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListFamiliarisationSectors.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\FamiliarisationSectors\Pages\ListFamiliarisationSectors::__invoke
* @see app/Filament/Resources/FamiliarisationSectors/Pages/ListFamiliarisationSectors.php:7
* @route '/admin/familiarisation-sectors'
*/
ListFamiliarisationSectorsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListFamiliarisationSectors.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

ListFamiliarisationSectors.form = ListFamiliarisationSectorsForm

export default ListFamiliarisationSectors