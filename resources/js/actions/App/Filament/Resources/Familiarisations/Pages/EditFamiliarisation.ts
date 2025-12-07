import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\Familiarisations\Pages\EditFamiliarisation::__invoke
* @see app/Filament/Resources/Familiarisations/Pages/EditFamiliarisation.php:7
* @route '/admin/familiarisations/{record}/edit'
*/
const EditFamiliarisation = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: EditFamiliarisation.url(args, options),
    method: 'get',
})

EditFamiliarisation.definition = {
    methods: ["get","head"],
    url: '/admin/familiarisations/{record}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\Familiarisations\Pages\EditFamiliarisation::__invoke
* @see app/Filament/Resources/Familiarisations/Pages/EditFamiliarisation.php:7
* @route '/admin/familiarisations/{record}/edit'
*/
EditFamiliarisation.url = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return EditFamiliarisation.definition.url
            .replace('{record}', parsedArgs.record.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Filament\Resources\Familiarisations\Pages\EditFamiliarisation::__invoke
* @see app/Filament/Resources/Familiarisations/Pages/EditFamiliarisation.php:7
* @route '/admin/familiarisations/{record}/edit'
*/
EditFamiliarisation.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: EditFamiliarisation.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Familiarisations\Pages\EditFamiliarisation::__invoke
* @see app/Filament/Resources/Familiarisations/Pages/EditFamiliarisation.php:7
* @route '/admin/familiarisations/{record}/edit'
*/
EditFamiliarisation.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: EditFamiliarisation.url(args, options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\Familiarisations\Pages\EditFamiliarisation::__invoke
* @see app/Filament/Resources/Familiarisations/Pages/EditFamiliarisation.php:7
* @route '/admin/familiarisations/{record}/edit'
*/
const EditFamiliarisationForm = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EditFamiliarisation.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Familiarisations\Pages\EditFamiliarisation::__invoke
* @see app/Filament/Resources/Familiarisations/Pages/EditFamiliarisation.php:7
* @route '/admin/familiarisations/{record}/edit'
*/
EditFamiliarisationForm.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EditFamiliarisation.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Familiarisations\Pages\EditFamiliarisation::__invoke
* @see app/Filament/Resources/Familiarisations/Pages/EditFamiliarisation.php:7
* @route '/admin/familiarisations/{record}/edit'
*/
EditFamiliarisationForm.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EditFamiliarisation.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

EditFamiliarisation.form = EditFamiliarisationForm

export default EditFamiliarisation