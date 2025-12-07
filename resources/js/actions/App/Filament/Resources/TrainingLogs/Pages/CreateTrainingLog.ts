import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\TrainingLogs\Pages\CreateTrainingLog::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/CreateTrainingLog.php:7
* @route '/admin/training-logs/create'
*/
const CreateTrainingLog = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: CreateTrainingLog.url(options),
    method: 'get',
})

CreateTrainingLog.definition = {
    methods: ["get","head"],
    url: '/admin/training-logs/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\TrainingLogs\Pages\CreateTrainingLog::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/CreateTrainingLog.php:7
* @route '/admin/training-logs/create'
*/
CreateTrainingLog.url = (options?: RouteQueryOptions) => {
    return CreateTrainingLog.definition.url + queryParams(options)
}

/**
* @see \App\Filament\Resources\TrainingLogs\Pages\CreateTrainingLog::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/CreateTrainingLog.php:7
* @route '/admin/training-logs/create'
*/
CreateTrainingLog.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: CreateTrainingLog.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\TrainingLogs\Pages\CreateTrainingLog::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/CreateTrainingLog.php:7
* @route '/admin/training-logs/create'
*/
CreateTrainingLog.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: CreateTrainingLog.url(options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\TrainingLogs\Pages\CreateTrainingLog::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/CreateTrainingLog.php:7
* @route '/admin/training-logs/create'
*/
const CreateTrainingLogForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: CreateTrainingLog.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\TrainingLogs\Pages\CreateTrainingLog::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/CreateTrainingLog.php:7
* @route '/admin/training-logs/create'
*/
CreateTrainingLogForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: CreateTrainingLog.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\TrainingLogs\Pages\CreateTrainingLog::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/CreateTrainingLog.php:7
* @route '/admin/training-logs/create'
*/
CreateTrainingLogForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: CreateTrainingLog.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

CreateTrainingLog.form = CreateTrainingLogForm

export default CreateTrainingLog