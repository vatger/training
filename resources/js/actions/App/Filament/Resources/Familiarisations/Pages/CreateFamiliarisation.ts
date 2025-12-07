import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\Familiarisations\Pages\CreateFamiliarisation::__invoke
* @see app/Filament/Resources/Familiarisations/Pages/CreateFamiliarisation.php:7
* @route '/admin/familiarisations/create'
*/
const CreateFamiliarisation = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: CreateFamiliarisation.url(options),
    method: 'get',
})

CreateFamiliarisation.definition = {
    methods: ["get","head"],
    url: '/admin/familiarisations/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\Familiarisations\Pages\CreateFamiliarisation::__invoke
* @see app/Filament/Resources/Familiarisations/Pages/CreateFamiliarisation.php:7
* @route '/admin/familiarisations/create'
*/
CreateFamiliarisation.url = (options?: RouteQueryOptions) => {
    return CreateFamiliarisation.definition.url + queryParams(options)
}

/**
* @see \App\Filament\Resources\Familiarisations\Pages\CreateFamiliarisation::__invoke
* @see app/Filament/Resources/Familiarisations/Pages/CreateFamiliarisation.php:7
* @route '/admin/familiarisations/create'
*/
CreateFamiliarisation.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: CreateFamiliarisation.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Familiarisations\Pages\CreateFamiliarisation::__invoke
* @see app/Filament/Resources/Familiarisations/Pages/CreateFamiliarisation.php:7
* @route '/admin/familiarisations/create'
*/
CreateFamiliarisation.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: CreateFamiliarisation.url(options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\Familiarisations\Pages\CreateFamiliarisation::__invoke
* @see app/Filament/Resources/Familiarisations/Pages/CreateFamiliarisation.php:7
* @route '/admin/familiarisations/create'
*/
const CreateFamiliarisationForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: CreateFamiliarisation.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Familiarisations\Pages\CreateFamiliarisation::__invoke
* @see app/Filament/Resources/Familiarisations/Pages/CreateFamiliarisation.php:7
* @route '/admin/familiarisations/create'
*/
CreateFamiliarisationForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: CreateFamiliarisation.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Familiarisations\Pages\CreateFamiliarisation::__invoke
* @see app/Filament/Resources/Familiarisations/Pages/CreateFamiliarisation.php:7
* @route '/admin/familiarisations/create'
*/
CreateFamiliarisationForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: CreateFamiliarisation.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

CreateFamiliarisation.form = CreateFamiliarisationForm

export default CreateFamiliarisation