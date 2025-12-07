import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\Cpts\Pages\ViewCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/ViewCpt.php:7
* @route '/admin/cpts/{record}'
*/
const ViewCpt = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ViewCpt.url(args, options),
    method: 'get',
})

ViewCpt.definition = {
    methods: ["get","head"],
    url: '/admin/cpts/{record}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\Cpts\Pages\ViewCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/ViewCpt.php:7
* @route '/admin/cpts/{record}'
*/
ViewCpt.url = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return ViewCpt.definition.url
            .replace('{record}', parsedArgs.record.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Filament\Resources\Cpts\Pages\ViewCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/ViewCpt.php:7
* @route '/admin/cpts/{record}'
*/
ViewCpt.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ViewCpt.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Cpts\Pages\ViewCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/ViewCpt.php:7
* @route '/admin/cpts/{record}'
*/
ViewCpt.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ViewCpt.url(args, options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\Cpts\Pages\ViewCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/ViewCpt.php:7
* @route '/admin/cpts/{record}'
*/
const ViewCptForm = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ViewCpt.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Cpts\Pages\ViewCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/ViewCpt.php:7
* @route '/admin/cpts/{record}'
*/
ViewCptForm.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ViewCpt.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Cpts\Pages\ViewCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/ViewCpt.php:7
* @route '/admin/cpts/{record}'
*/
ViewCptForm.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ViewCpt.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

ViewCpt.form = ViewCptForm

export default ViewCpt