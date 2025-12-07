import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\Roles\Pages\EditRole::__invoke
* @see app/Filament/Resources/Roles/Pages/EditRole.php:7
* @route '/admin/roles/{record}/edit'
*/
const EditRole = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: EditRole.url(args, options),
    method: 'get',
})

EditRole.definition = {
    methods: ["get","head"],
    url: '/admin/roles/{record}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\Roles\Pages\EditRole::__invoke
* @see app/Filament/Resources/Roles/Pages/EditRole.php:7
* @route '/admin/roles/{record}/edit'
*/
EditRole.url = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return EditRole.definition.url
            .replace('{record}', parsedArgs.record.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Filament\Resources\Roles\Pages\EditRole::__invoke
* @see app/Filament/Resources/Roles/Pages/EditRole.php:7
* @route '/admin/roles/{record}/edit'
*/
EditRole.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: EditRole.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Roles\Pages\EditRole::__invoke
* @see app/Filament/Resources/Roles/Pages/EditRole.php:7
* @route '/admin/roles/{record}/edit'
*/
EditRole.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: EditRole.url(args, options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\Roles\Pages\EditRole::__invoke
* @see app/Filament/Resources/Roles/Pages/EditRole.php:7
* @route '/admin/roles/{record}/edit'
*/
const EditRoleForm = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EditRole.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Roles\Pages\EditRole::__invoke
* @see app/Filament/Resources/Roles/Pages/EditRole.php:7
* @route '/admin/roles/{record}/edit'
*/
EditRoleForm.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EditRole.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Roles\Pages\EditRole::__invoke
* @see app/Filament/Resources/Roles/Pages/EditRole.php:7
* @route '/admin/roles/{record}/edit'
*/
EditRoleForm.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EditRole.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

EditRole.form = EditRoleForm

export default EditRole