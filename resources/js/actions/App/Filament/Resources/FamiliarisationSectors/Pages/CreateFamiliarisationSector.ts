import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\FamiliarisationSectors\Pages\CreateFamiliarisationSector::__invoke
* @see app/Filament/Resources/FamiliarisationSectors/Pages/CreateFamiliarisationSector.php:7
* @route '/admin/familiarisation-sectors/create'
*/
const CreateFamiliarisationSector = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: CreateFamiliarisationSector.url(options),
    method: 'get',
})

CreateFamiliarisationSector.definition = {
    methods: ["get","head"],
    url: '/admin/familiarisation-sectors/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\FamiliarisationSectors\Pages\CreateFamiliarisationSector::__invoke
* @see app/Filament/Resources/FamiliarisationSectors/Pages/CreateFamiliarisationSector.php:7
* @route '/admin/familiarisation-sectors/create'
*/
CreateFamiliarisationSector.url = (options?: RouteQueryOptions) => {
    return CreateFamiliarisationSector.definition.url + queryParams(options)
}

/**
* @see \App\Filament\Resources\FamiliarisationSectors\Pages\CreateFamiliarisationSector::__invoke
* @see app/Filament/Resources/FamiliarisationSectors/Pages/CreateFamiliarisationSector.php:7
* @route '/admin/familiarisation-sectors/create'
*/
CreateFamiliarisationSector.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: CreateFamiliarisationSector.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\FamiliarisationSectors\Pages\CreateFamiliarisationSector::__invoke
* @see app/Filament/Resources/FamiliarisationSectors/Pages/CreateFamiliarisationSector.php:7
* @route '/admin/familiarisation-sectors/create'
*/
CreateFamiliarisationSector.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: CreateFamiliarisationSector.url(options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\FamiliarisationSectors\Pages\CreateFamiliarisationSector::__invoke
* @see app/Filament/Resources/FamiliarisationSectors/Pages/CreateFamiliarisationSector.php:7
* @route '/admin/familiarisation-sectors/create'
*/
const CreateFamiliarisationSectorForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: CreateFamiliarisationSector.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\FamiliarisationSectors\Pages\CreateFamiliarisationSector::__invoke
* @see app/Filament/Resources/FamiliarisationSectors/Pages/CreateFamiliarisationSector.php:7
* @route '/admin/familiarisation-sectors/create'
*/
CreateFamiliarisationSectorForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: CreateFamiliarisationSector.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\FamiliarisationSectors\Pages\CreateFamiliarisationSector::__invoke
* @see app/Filament/Resources/FamiliarisationSectors/Pages/CreateFamiliarisationSector.php:7
* @route '/admin/familiarisation-sectors/create'
*/
CreateFamiliarisationSectorForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: CreateFamiliarisationSector.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

CreateFamiliarisationSector.form = CreateFamiliarisationSectorForm

export default CreateFamiliarisationSector