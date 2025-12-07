import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\TrainingLogs\Pages\ViewTrainingLog::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/ViewTrainingLog.php:7
* @route '/admin/training-logs/{record}'
*/
const ViewTrainingLog = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ViewTrainingLog.url(args, options),
    method: 'get',
})

ViewTrainingLog.definition = {
    methods: ["get","head"],
    url: '/admin/training-logs/{record}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\TrainingLogs\Pages\ViewTrainingLog::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/ViewTrainingLog.php:7
* @route '/admin/training-logs/{record}'
*/
ViewTrainingLog.url = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return ViewTrainingLog.definition.url
            .replace('{record}', parsedArgs.record.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Filament\Resources\TrainingLogs\Pages\ViewTrainingLog::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/ViewTrainingLog.php:7
* @route '/admin/training-logs/{record}'
*/
ViewTrainingLog.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ViewTrainingLog.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\TrainingLogs\Pages\ViewTrainingLog::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/ViewTrainingLog.php:7
* @route '/admin/training-logs/{record}'
*/
ViewTrainingLog.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ViewTrainingLog.url(args, options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\TrainingLogs\Pages\ViewTrainingLog::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/ViewTrainingLog.php:7
* @route '/admin/training-logs/{record}'
*/
const ViewTrainingLogForm = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ViewTrainingLog.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\TrainingLogs\Pages\ViewTrainingLog::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/ViewTrainingLog.php:7
* @route '/admin/training-logs/{record}'
*/
ViewTrainingLogForm.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ViewTrainingLog.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\TrainingLogs\Pages\ViewTrainingLog::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/ViewTrainingLog.php:7
* @route '/admin/training-logs/{record}'
*/
ViewTrainingLogForm.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ViewTrainingLog.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

ViewTrainingLog.form = ViewTrainingLogForm

export default ViewTrainingLog