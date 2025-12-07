import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\EndorsementActivities\Pages\ListEndorsementActivities::__invoke
* @see app/Filament/Resources/EndorsementActivities/Pages/ListEndorsementActivities.php:7
* @route '/admin/endorsement-activities'
*/
const ListEndorsementActivities = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ListEndorsementActivities.url(options),
    method: 'get',
})

ListEndorsementActivities.definition = {
    methods: ["get","head"],
    url: '/admin/endorsement-activities',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\EndorsementActivities\Pages\ListEndorsementActivities::__invoke
* @see app/Filament/Resources/EndorsementActivities/Pages/ListEndorsementActivities.php:7
* @route '/admin/endorsement-activities'
*/
ListEndorsementActivities.url = (options?: RouteQueryOptions) => {
    return ListEndorsementActivities.definition.url + queryParams(options)
}

/**
* @see \App\Filament\Resources\EndorsementActivities\Pages\ListEndorsementActivities::__invoke
* @see app/Filament/Resources/EndorsementActivities/Pages/ListEndorsementActivities.php:7
* @route '/admin/endorsement-activities'
*/
ListEndorsementActivities.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ListEndorsementActivities.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\EndorsementActivities\Pages\ListEndorsementActivities::__invoke
* @see app/Filament/Resources/EndorsementActivities/Pages/ListEndorsementActivities.php:7
* @route '/admin/endorsement-activities'
*/
ListEndorsementActivities.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ListEndorsementActivities.url(options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\EndorsementActivities\Pages\ListEndorsementActivities::__invoke
* @see app/Filament/Resources/EndorsementActivities/Pages/ListEndorsementActivities.php:7
* @route '/admin/endorsement-activities'
*/
const ListEndorsementActivitiesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListEndorsementActivities.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\EndorsementActivities\Pages\ListEndorsementActivities::__invoke
* @see app/Filament/Resources/EndorsementActivities/Pages/ListEndorsementActivities.php:7
* @route '/admin/endorsement-activities'
*/
ListEndorsementActivitiesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListEndorsementActivities.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\EndorsementActivities\Pages\ListEndorsementActivities::__invoke
* @see app/Filament/Resources/EndorsementActivities/Pages/ListEndorsementActivities.php:7
* @route '/admin/endorsement-activities'
*/
ListEndorsementActivitiesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListEndorsementActivities.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

ListEndorsementActivities.form = ListEndorsementActivitiesForm

export default ListEndorsementActivities