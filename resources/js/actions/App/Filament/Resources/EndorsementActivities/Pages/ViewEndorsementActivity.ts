import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\EndorsementActivities\Pages\ViewEndorsementActivity::__invoke
* @see app/Filament/Resources/EndorsementActivities/Pages/ViewEndorsementActivity.php:7
* @route '/admin/endorsement-activities/{record}'
*/
const ViewEndorsementActivity = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ViewEndorsementActivity.url(args, options),
    method: 'get',
})

ViewEndorsementActivity.definition = {
    methods: ["get","head"],
    url: '/admin/endorsement-activities/{record}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\EndorsementActivities\Pages\ViewEndorsementActivity::__invoke
* @see app/Filament/Resources/EndorsementActivities/Pages/ViewEndorsementActivity.php:7
* @route '/admin/endorsement-activities/{record}'
*/
ViewEndorsementActivity.url = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return ViewEndorsementActivity.definition.url
            .replace('{record}', parsedArgs.record.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Filament\Resources\EndorsementActivities\Pages\ViewEndorsementActivity::__invoke
* @see app/Filament/Resources/EndorsementActivities/Pages/ViewEndorsementActivity.php:7
* @route '/admin/endorsement-activities/{record}'
*/
ViewEndorsementActivity.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ViewEndorsementActivity.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\EndorsementActivities\Pages\ViewEndorsementActivity::__invoke
* @see app/Filament/Resources/EndorsementActivities/Pages/ViewEndorsementActivity.php:7
* @route '/admin/endorsement-activities/{record}'
*/
ViewEndorsementActivity.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ViewEndorsementActivity.url(args, options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\EndorsementActivities\Pages\ViewEndorsementActivity::__invoke
* @see app/Filament/Resources/EndorsementActivities/Pages/ViewEndorsementActivity.php:7
* @route '/admin/endorsement-activities/{record}'
*/
const ViewEndorsementActivityForm = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ViewEndorsementActivity.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\EndorsementActivities\Pages\ViewEndorsementActivity::__invoke
* @see app/Filament/Resources/EndorsementActivities/Pages/ViewEndorsementActivity.php:7
* @route '/admin/endorsement-activities/{record}'
*/
ViewEndorsementActivityForm.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ViewEndorsementActivity.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\EndorsementActivities\Pages\ViewEndorsementActivity::__invoke
* @see app/Filament/Resources/EndorsementActivities/Pages/ViewEndorsementActivity.php:7
* @route '/admin/endorsement-activities/{record}'
*/
ViewEndorsementActivityForm.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ViewEndorsementActivity.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

ViewEndorsementActivity.form = ViewEndorsementActivityForm

export default ViewEndorsementActivity