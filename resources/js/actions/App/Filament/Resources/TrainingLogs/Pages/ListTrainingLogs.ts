import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\TrainingLogs\Pages\ListTrainingLogs::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/ListTrainingLogs.php:7
* @route '/admin/training-logs'
*/
const ListTrainingLogs = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ListTrainingLogs.url(options),
    method: 'get',
})

ListTrainingLogs.definition = {
    methods: ["get","head"],
    url: '/admin/training-logs',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\TrainingLogs\Pages\ListTrainingLogs::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/ListTrainingLogs.php:7
* @route '/admin/training-logs'
*/
ListTrainingLogs.url = (options?: RouteQueryOptions) => {
    return ListTrainingLogs.definition.url + queryParams(options)
}

/**
* @see \App\Filament\Resources\TrainingLogs\Pages\ListTrainingLogs::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/ListTrainingLogs.php:7
* @route '/admin/training-logs'
*/
ListTrainingLogs.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ListTrainingLogs.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\TrainingLogs\Pages\ListTrainingLogs::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/ListTrainingLogs.php:7
* @route '/admin/training-logs'
*/
ListTrainingLogs.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ListTrainingLogs.url(options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\TrainingLogs\Pages\ListTrainingLogs::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/ListTrainingLogs.php:7
* @route '/admin/training-logs'
*/
const ListTrainingLogsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListTrainingLogs.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\TrainingLogs\Pages\ListTrainingLogs::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/ListTrainingLogs.php:7
* @route '/admin/training-logs'
*/
ListTrainingLogsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListTrainingLogs.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\TrainingLogs\Pages\ListTrainingLogs::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/ListTrainingLogs.php:7
* @route '/admin/training-logs'
*/
ListTrainingLogsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListTrainingLogs.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

ListTrainingLogs.form = ListTrainingLogsForm

export default ListTrainingLogs