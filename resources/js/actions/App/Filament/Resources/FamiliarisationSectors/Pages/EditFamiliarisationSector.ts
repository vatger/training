import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\FamiliarisationSectors\Pages\EditFamiliarisationSector::__invoke
* @see app/Filament/Resources/FamiliarisationSectors/Pages/EditFamiliarisationSector.php:7
* @route '/admin/familiarisation-sectors/{record}/edit'
*/
const EditFamiliarisationSector = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: EditFamiliarisationSector.url(args, options),
    method: 'get',
})

EditFamiliarisationSector.definition = {
    methods: ["get","head"],
    url: '/admin/familiarisation-sectors/{record}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\FamiliarisationSectors\Pages\EditFamiliarisationSector::__invoke
* @see app/Filament/Resources/FamiliarisationSectors/Pages/EditFamiliarisationSector.php:7
* @route '/admin/familiarisation-sectors/{record}/edit'
*/
EditFamiliarisationSector.url = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return EditFamiliarisationSector.definition.url
            .replace('{record}', parsedArgs.record.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Filament\Resources\FamiliarisationSectors\Pages\EditFamiliarisationSector::__invoke
* @see app/Filament/Resources/FamiliarisationSectors/Pages/EditFamiliarisationSector.php:7
* @route '/admin/familiarisation-sectors/{record}/edit'
*/
EditFamiliarisationSector.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: EditFamiliarisationSector.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\FamiliarisationSectors\Pages\EditFamiliarisationSector::__invoke
* @see app/Filament/Resources/FamiliarisationSectors/Pages/EditFamiliarisationSector.php:7
* @route '/admin/familiarisation-sectors/{record}/edit'
*/
EditFamiliarisationSector.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: EditFamiliarisationSector.url(args, options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\FamiliarisationSectors\Pages\EditFamiliarisationSector::__invoke
* @see app/Filament/Resources/FamiliarisationSectors/Pages/EditFamiliarisationSector.php:7
* @route '/admin/familiarisation-sectors/{record}/edit'
*/
const EditFamiliarisationSectorForm = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EditFamiliarisationSector.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\FamiliarisationSectors\Pages\EditFamiliarisationSector::__invoke
* @see app/Filament/Resources/FamiliarisationSectors/Pages/EditFamiliarisationSector.php:7
* @route '/admin/familiarisation-sectors/{record}/edit'
*/
EditFamiliarisationSectorForm.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EditFamiliarisationSector.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\FamiliarisationSectors\Pages\EditFamiliarisationSector::__invoke
* @see app/Filament/Resources/FamiliarisationSectors/Pages/EditFamiliarisationSector.php:7
* @route '/admin/familiarisation-sectors/{record}/edit'
*/
EditFamiliarisationSectorForm.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EditFamiliarisationSector.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

EditFamiliarisationSector.form = EditFamiliarisationSectorForm

export default EditFamiliarisationSector