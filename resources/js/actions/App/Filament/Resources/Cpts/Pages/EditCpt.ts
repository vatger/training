import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\Cpts\Pages\EditCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/EditCpt.php:7
* @route '/admin/cpts/{record}/edit'
*/
const EditCpt = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: EditCpt.url(args, options),
    method: 'get',
})

EditCpt.definition = {
    methods: ["get","head"],
    url: '/admin/cpts/{record}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\Cpts\Pages\EditCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/EditCpt.php:7
* @route '/admin/cpts/{record}/edit'
*/
EditCpt.url = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return EditCpt.definition.url
            .replace('{record}', parsedArgs.record.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Filament\Resources\Cpts\Pages\EditCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/EditCpt.php:7
* @route '/admin/cpts/{record}/edit'
*/
EditCpt.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: EditCpt.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Cpts\Pages\EditCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/EditCpt.php:7
* @route '/admin/cpts/{record}/edit'
*/
EditCpt.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: EditCpt.url(args, options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\Cpts\Pages\EditCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/EditCpt.php:7
* @route '/admin/cpts/{record}/edit'
*/
const EditCptForm = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EditCpt.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Cpts\Pages\EditCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/EditCpt.php:7
* @route '/admin/cpts/{record}/edit'
*/
EditCptForm.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EditCpt.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Cpts\Pages\EditCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/EditCpt.php:7
* @route '/admin/cpts/{record}/edit'
*/
EditCptForm.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EditCpt.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

EditCpt.form = EditCptForm

export default EditCpt