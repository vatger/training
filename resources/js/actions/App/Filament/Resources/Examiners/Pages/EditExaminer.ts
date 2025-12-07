import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\Examiners\Pages\EditExaminer::__invoke
* @see app/Filament/Resources/Examiners/Pages/EditExaminer.php:7
* @route '/admin/examiners/{record}/edit'
*/
const EditExaminer = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: EditExaminer.url(args, options),
    method: 'get',
})

EditExaminer.definition = {
    methods: ["get","head"],
    url: '/admin/examiners/{record}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\Examiners\Pages\EditExaminer::__invoke
* @see app/Filament/Resources/Examiners/Pages/EditExaminer.php:7
* @route '/admin/examiners/{record}/edit'
*/
EditExaminer.url = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return EditExaminer.definition.url
            .replace('{record}', parsedArgs.record.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Filament\Resources\Examiners\Pages\EditExaminer::__invoke
* @see app/Filament/Resources/Examiners/Pages/EditExaminer.php:7
* @route '/admin/examiners/{record}/edit'
*/
EditExaminer.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: EditExaminer.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Examiners\Pages\EditExaminer::__invoke
* @see app/Filament/Resources/Examiners/Pages/EditExaminer.php:7
* @route '/admin/examiners/{record}/edit'
*/
EditExaminer.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: EditExaminer.url(args, options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\Examiners\Pages\EditExaminer::__invoke
* @see app/Filament/Resources/Examiners/Pages/EditExaminer.php:7
* @route '/admin/examiners/{record}/edit'
*/
const EditExaminerForm = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EditExaminer.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Examiners\Pages\EditExaminer::__invoke
* @see app/Filament/Resources/Examiners/Pages/EditExaminer.php:7
* @route '/admin/examiners/{record}/edit'
*/
EditExaminerForm.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EditExaminer.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Examiners\Pages\EditExaminer::__invoke
* @see app/Filament/Resources/Examiners/Pages/EditExaminer.php:7
* @route '/admin/examiners/{record}/edit'
*/
EditExaminerForm.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EditExaminer.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

EditExaminer.form = EditExaminerForm

export default EditExaminer