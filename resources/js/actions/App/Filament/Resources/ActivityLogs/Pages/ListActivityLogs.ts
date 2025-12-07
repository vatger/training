import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs::__invoke
* @see app/Filament/Resources/ActivityLogs/Pages/ListActivityLogs.php:7
* @route '/admin/activity-logs'
*/
const ListActivityLogs = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ListActivityLogs.url(options),
    method: 'get',
})

ListActivityLogs.definition = {
    methods: ["get","head"],
    url: '/admin/activity-logs',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs::__invoke
* @see app/Filament/Resources/ActivityLogs/Pages/ListActivityLogs.php:7
* @route '/admin/activity-logs'
*/
ListActivityLogs.url = (options?: RouteQueryOptions) => {
    return ListActivityLogs.definition.url + queryParams(options)
}

/**
* @see \App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs::__invoke
* @see app/Filament/Resources/ActivityLogs/Pages/ListActivityLogs.php:7
* @route '/admin/activity-logs'
*/
ListActivityLogs.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ListActivityLogs.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs::__invoke
* @see app/Filament/Resources/ActivityLogs/Pages/ListActivityLogs.php:7
* @route '/admin/activity-logs'
*/
ListActivityLogs.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ListActivityLogs.url(options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs::__invoke
* @see app/Filament/Resources/ActivityLogs/Pages/ListActivityLogs.php:7
* @route '/admin/activity-logs'
*/
const ListActivityLogsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListActivityLogs.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs::__invoke
* @see app/Filament/Resources/ActivityLogs/Pages/ListActivityLogs.php:7
* @route '/admin/activity-logs'
*/
ListActivityLogsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListActivityLogs.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs::__invoke
* @see app/Filament/Resources/ActivityLogs/Pages/ListActivityLogs.php:7
* @route '/admin/activity-logs'
*/
ListActivityLogsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListActivityLogs.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

ListActivityLogs.form = ListActivityLogsForm

export default ListActivityLogs