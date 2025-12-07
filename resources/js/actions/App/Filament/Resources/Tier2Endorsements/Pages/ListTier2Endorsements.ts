import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\Tier2Endorsements\Pages\ListTier2Endorsements::__invoke
* @see app/Filament/Resources/Tier2Endorsements/Pages/ListTier2Endorsements.php:7
* @route '/admin/tier2-endorsements'
*/
const ListTier2Endorsements = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ListTier2Endorsements.url(options),
    method: 'get',
})

ListTier2Endorsements.definition = {
    methods: ["get","head"],
    url: '/admin/tier2-endorsements',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\Tier2Endorsements\Pages\ListTier2Endorsements::__invoke
* @see app/Filament/Resources/Tier2Endorsements/Pages/ListTier2Endorsements.php:7
* @route '/admin/tier2-endorsements'
*/
ListTier2Endorsements.url = (options?: RouteQueryOptions) => {
    return ListTier2Endorsements.definition.url + queryParams(options)
}

/**
* @see \App\Filament\Resources\Tier2Endorsements\Pages\ListTier2Endorsements::__invoke
* @see app/Filament/Resources/Tier2Endorsements/Pages/ListTier2Endorsements.php:7
* @route '/admin/tier2-endorsements'
*/
ListTier2Endorsements.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ListTier2Endorsements.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Tier2Endorsements\Pages\ListTier2Endorsements::__invoke
* @see app/Filament/Resources/Tier2Endorsements/Pages/ListTier2Endorsements.php:7
* @route '/admin/tier2-endorsements'
*/
ListTier2Endorsements.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ListTier2Endorsements.url(options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\Tier2Endorsements\Pages\ListTier2Endorsements::__invoke
* @see app/Filament/Resources/Tier2Endorsements/Pages/ListTier2Endorsements.php:7
* @route '/admin/tier2-endorsements'
*/
const ListTier2EndorsementsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListTier2Endorsements.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Tier2Endorsements\Pages\ListTier2Endorsements::__invoke
* @see app/Filament/Resources/Tier2Endorsements/Pages/ListTier2Endorsements.php:7
* @route '/admin/tier2-endorsements'
*/
ListTier2EndorsementsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListTier2Endorsements.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Tier2Endorsements\Pages\ListTier2Endorsements::__invoke
* @see app/Filament/Resources/Tier2Endorsements/Pages/ListTier2Endorsements.php:7
* @route '/admin/tier2-endorsements'
*/
ListTier2EndorsementsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListTier2Endorsements.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

ListTier2Endorsements.form = ListTier2EndorsementsForm

export default ListTier2Endorsements