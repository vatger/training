import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\Tier2Endorsements\Pages\CreateTier2Endorsement::__invoke
* @see app/Filament/Resources/Tier2Endorsements/Pages/CreateTier2Endorsement.php:7
* @route '/admin/tier2-endorsements/create'
*/
const CreateTier2Endorsement = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: CreateTier2Endorsement.url(options),
    method: 'get',
})

CreateTier2Endorsement.definition = {
    methods: ["get","head"],
    url: '/admin/tier2-endorsements/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\Tier2Endorsements\Pages\CreateTier2Endorsement::__invoke
* @see app/Filament/Resources/Tier2Endorsements/Pages/CreateTier2Endorsement.php:7
* @route '/admin/tier2-endorsements/create'
*/
CreateTier2Endorsement.url = (options?: RouteQueryOptions) => {
    return CreateTier2Endorsement.definition.url + queryParams(options)
}

/**
* @see \App\Filament\Resources\Tier2Endorsements\Pages\CreateTier2Endorsement::__invoke
* @see app/Filament/Resources/Tier2Endorsements/Pages/CreateTier2Endorsement.php:7
* @route '/admin/tier2-endorsements/create'
*/
CreateTier2Endorsement.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: CreateTier2Endorsement.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Tier2Endorsements\Pages\CreateTier2Endorsement::__invoke
* @see app/Filament/Resources/Tier2Endorsements/Pages/CreateTier2Endorsement.php:7
* @route '/admin/tier2-endorsements/create'
*/
CreateTier2Endorsement.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: CreateTier2Endorsement.url(options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\Tier2Endorsements\Pages\CreateTier2Endorsement::__invoke
* @see app/Filament/Resources/Tier2Endorsements/Pages/CreateTier2Endorsement.php:7
* @route '/admin/tier2-endorsements/create'
*/
const CreateTier2EndorsementForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: CreateTier2Endorsement.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Tier2Endorsements\Pages\CreateTier2Endorsement::__invoke
* @see app/Filament/Resources/Tier2Endorsements/Pages/CreateTier2Endorsement.php:7
* @route '/admin/tier2-endorsements/create'
*/
CreateTier2EndorsementForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: CreateTier2Endorsement.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Tier2Endorsements\Pages\CreateTier2Endorsement::__invoke
* @see app/Filament/Resources/Tier2Endorsements/Pages/CreateTier2Endorsement.php:7
* @route '/admin/tier2-endorsements/create'
*/
CreateTier2EndorsementForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: CreateTier2Endorsement.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

CreateTier2Endorsement.form = CreateTier2EndorsementForm

export default CreateTier2Endorsement