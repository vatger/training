import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\ActivityLogs\Pages\ViewActivityLog::__invoke
* @see app/Filament/Resources/ActivityLogs/Pages/ViewActivityLog.php:7
* @route '/admin/activity-logs/{record}'
*/
const ViewActivityLog = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ViewActivityLog.url(args, options),
    method: 'get',
})

ViewActivityLog.definition = {
    methods: ["get","head"],
    url: '/admin/activity-logs/{record}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\ActivityLogs\Pages\ViewActivityLog::__invoke
* @see app/Filament/Resources/ActivityLogs/Pages/ViewActivityLog.php:7
* @route '/admin/activity-logs/{record}'
*/
ViewActivityLog.url = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return ViewActivityLog.definition.url
            .replace('{record}', parsedArgs.record.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Filament\Resources\ActivityLogs\Pages\ViewActivityLog::__invoke
* @see app/Filament/Resources/ActivityLogs/Pages/ViewActivityLog.php:7
* @route '/admin/activity-logs/{record}'
*/
ViewActivityLog.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ViewActivityLog.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\ActivityLogs\Pages\ViewActivityLog::__invoke
* @see app/Filament/Resources/ActivityLogs/Pages/ViewActivityLog.php:7
* @route '/admin/activity-logs/{record}'
*/
ViewActivityLog.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ViewActivityLog.url(args, options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\ActivityLogs\Pages\ViewActivityLog::__invoke
* @see app/Filament/Resources/ActivityLogs/Pages/ViewActivityLog.php:7
* @route '/admin/activity-logs/{record}'
*/
const ViewActivityLogForm = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ViewActivityLog.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\ActivityLogs\Pages\ViewActivityLog::__invoke
* @see app/Filament/Resources/ActivityLogs/Pages/ViewActivityLog.php:7
* @route '/admin/activity-logs/{record}'
*/
ViewActivityLogForm.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ViewActivityLog.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\ActivityLogs\Pages\ViewActivityLog::__invoke
* @see app/Filament/Resources/ActivityLogs/Pages/ViewActivityLog.php:7
* @route '/admin/activity-logs/{record}'
*/
ViewActivityLogForm.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ViewActivityLog.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

ViewActivityLog.form = ViewActivityLogForm

export default ViewActivityLog