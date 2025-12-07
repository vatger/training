import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Filament\Resources\Cpts\Pages\ListCpts::__invoke
* @see app/Filament/Resources/Cpts/Pages/ListCpts.php:7
* @route '/admin/cpts'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/cpts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\Cpts\Pages\ListCpts::__invoke
* @see app/Filament/Resources/Cpts/Pages/ListCpts.php:7
* @route '/admin/cpts'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Filament\Resources\Cpts\Pages\ListCpts::__invoke
* @see app/Filament/Resources/Cpts/Pages/ListCpts.php:7
* @route '/admin/cpts'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Cpts\Pages\ListCpts::__invoke
* @see app/Filament/Resources/Cpts/Pages/ListCpts.php:7
* @route '/admin/cpts'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\Cpts\Pages\ListCpts::__invoke
* @see app/Filament/Resources/Cpts/Pages/ListCpts.php:7
* @route '/admin/cpts'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Cpts\Pages\ListCpts::__invoke
* @see app/Filament/Resources/Cpts/Pages/ListCpts.php:7
* @route '/admin/cpts'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Cpts\Pages\ListCpts::__invoke
* @see app/Filament/Resources/Cpts/Pages/ListCpts.php:7
* @route '/admin/cpts'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Filament\Resources\Cpts\Pages\ViewCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/ViewCpt.php:7
* @route '/admin/cpts/{record}'
*/
export const view = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: view.url(args, options),
    method: 'get',
})

view.definition = {
    methods: ["get","head"],
    url: '/admin/cpts/{record}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\Cpts\Pages\ViewCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/ViewCpt.php:7
* @route '/admin/cpts/{record}'
*/
view.url = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return view.definition.url
            .replace('{record}', parsedArgs.record.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Filament\Resources\Cpts\Pages\ViewCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/ViewCpt.php:7
* @route '/admin/cpts/{record}'
*/
view.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: view.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Cpts\Pages\ViewCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/ViewCpt.php:7
* @route '/admin/cpts/{record}'
*/
view.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: view.url(args, options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\Cpts\Pages\ViewCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/ViewCpt.php:7
* @route '/admin/cpts/{record}'
*/
const viewForm = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: view.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Cpts\Pages\ViewCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/ViewCpt.php:7
* @route '/admin/cpts/{record}'
*/
viewForm.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: view.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Cpts\Pages\ViewCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/ViewCpt.php:7
* @route '/admin/cpts/{record}'
*/
viewForm.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: view.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

view.form = viewForm

/**
* @see \App\Filament\Resources\Cpts\Pages\EditCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/EditCpt.php:7
* @route '/admin/cpts/{record}/edit'
*/
export const edit = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/admin/cpts/{record}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\Cpts\Pages\EditCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/EditCpt.php:7
* @route '/admin/cpts/{record}/edit'
*/
edit.url = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return edit.definition.url
            .replace('{record}', parsedArgs.record.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Filament\Resources\Cpts\Pages\EditCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/EditCpt.php:7
* @route '/admin/cpts/{record}/edit'
*/
edit.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Cpts\Pages\EditCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/EditCpt.php:7
* @route '/admin/cpts/{record}/edit'
*/
edit.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\Cpts\Pages\EditCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/EditCpt.php:7
* @route '/admin/cpts/{record}/edit'
*/
const editForm = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Cpts\Pages\EditCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/EditCpt.php:7
* @route '/admin/cpts/{record}/edit'
*/
editForm.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Cpts\Pages\EditCpt::__invoke
* @see app/Filament/Resources/Cpts/Pages/EditCpt.php:7
* @route '/admin/cpts/{record}/edit'
*/
editForm.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

edit.form = editForm

const cpts = {
    index: Object.assign(index, index),
    view: Object.assign(view, view),
    edit: Object.assign(edit, edit),
}

export default cpts