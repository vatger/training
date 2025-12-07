import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\TrainingLogs\Pages\EditTrainingLog::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/EditTrainingLog.php:7
* @route '/admin/training-logs/{record}/edit'
*/
const EditTrainingLog = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: EditTrainingLog.url(args, options),
    method: 'get',
})

EditTrainingLog.definition = {
    methods: ["get","head"],
    url: '/admin/training-logs/{record}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\TrainingLogs\Pages\EditTrainingLog::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/EditTrainingLog.php:7
* @route '/admin/training-logs/{record}/edit'
*/
EditTrainingLog.url = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return EditTrainingLog.definition.url
            .replace('{record}', parsedArgs.record.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Filament\Resources\TrainingLogs\Pages\EditTrainingLog::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/EditTrainingLog.php:7
* @route '/admin/training-logs/{record}/edit'
*/
EditTrainingLog.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: EditTrainingLog.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\TrainingLogs\Pages\EditTrainingLog::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/EditTrainingLog.php:7
* @route '/admin/training-logs/{record}/edit'
*/
EditTrainingLog.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: EditTrainingLog.url(args, options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\TrainingLogs\Pages\EditTrainingLog::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/EditTrainingLog.php:7
* @route '/admin/training-logs/{record}/edit'
*/
const EditTrainingLogForm = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EditTrainingLog.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\TrainingLogs\Pages\EditTrainingLog::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/EditTrainingLog.php:7
* @route '/admin/training-logs/{record}/edit'
*/
EditTrainingLogForm.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EditTrainingLog.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\TrainingLogs\Pages\EditTrainingLog::__invoke
* @see app/Filament/Resources/TrainingLogs/Pages/EditTrainingLog.php:7
* @route '/admin/training-logs/{record}/edit'
*/
EditTrainingLogForm.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EditTrainingLog.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

EditTrainingLog.form = EditTrainingLogForm

export default EditTrainingLog