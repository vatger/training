import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\Tier2Endorsements\Pages\EditTier2Endorsement::__invoke
* @see app/Filament/Resources/Tier2Endorsements/Pages/EditTier2Endorsement.php:7
* @route '/admin/tier2-endorsements/{record}/edit'
*/
const EditTier2Endorsement = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: EditTier2Endorsement.url(args, options),
    method: 'get',
})

EditTier2Endorsement.definition = {
    methods: ["get","head"],
    url: '/admin/tier2-endorsements/{record}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\Tier2Endorsements\Pages\EditTier2Endorsement::__invoke
* @see app/Filament/Resources/Tier2Endorsements/Pages/EditTier2Endorsement.php:7
* @route '/admin/tier2-endorsements/{record}/edit'
*/
EditTier2Endorsement.url = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return EditTier2Endorsement.definition.url
            .replace('{record}', parsedArgs.record.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Filament\Resources\Tier2Endorsements\Pages\EditTier2Endorsement::__invoke
* @see app/Filament/Resources/Tier2Endorsements/Pages/EditTier2Endorsement.php:7
* @route '/admin/tier2-endorsements/{record}/edit'
*/
EditTier2Endorsement.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: EditTier2Endorsement.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Tier2Endorsements\Pages\EditTier2Endorsement::__invoke
* @see app/Filament/Resources/Tier2Endorsements/Pages/EditTier2Endorsement.php:7
* @route '/admin/tier2-endorsements/{record}/edit'
*/
EditTier2Endorsement.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: EditTier2Endorsement.url(args, options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\Tier2Endorsements\Pages\EditTier2Endorsement::__invoke
* @see app/Filament/Resources/Tier2Endorsements/Pages/EditTier2Endorsement.php:7
* @route '/admin/tier2-endorsements/{record}/edit'
*/
const EditTier2EndorsementForm = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EditTier2Endorsement.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Tier2Endorsements\Pages\EditTier2Endorsement::__invoke
* @see app/Filament/Resources/Tier2Endorsements/Pages/EditTier2Endorsement.php:7
* @route '/admin/tier2-endorsements/{record}/edit'
*/
EditTier2EndorsementForm.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EditTier2Endorsement.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Tier2Endorsements\Pages\EditTier2Endorsement::__invoke
* @see app/Filament/Resources/Tier2Endorsements/Pages/EditTier2Endorsement.php:7
* @route '/admin/tier2-endorsements/{record}/edit'
*/
EditTier2EndorsementForm.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EditTier2Endorsement.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

EditTier2Endorsement.form = EditTier2EndorsementForm

export default EditTier2Endorsement