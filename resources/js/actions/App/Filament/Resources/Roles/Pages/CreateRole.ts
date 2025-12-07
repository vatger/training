import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\Roles\Pages\CreateRole::__invoke
* @see app/Filament/Resources/Roles/Pages/CreateRole.php:7
* @route '/admin/roles/create'
*/
const CreateRole = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: CreateRole.url(options),
    method: 'get',
})

CreateRole.definition = {
    methods: ["get","head"],
    url: '/admin/roles/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\Roles\Pages\CreateRole::__invoke
* @see app/Filament/Resources/Roles/Pages/CreateRole.php:7
* @route '/admin/roles/create'
*/
CreateRole.url = (options?: RouteQueryOptions) => {
    return CreateRole.definition.url + queryParams(options)
}

/**
* @see \App\Filament\Resources\Roles\Pages\CreateRole::__invoke
* @see app/Filament/Resources/Roles/Pages/CreateRole.php:7
* @route '/admin/roles/create'
*/
CreateRole.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: CreateRole.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Roles\Pages\CreateRole::__invoke
* @see app/Filament/Resources/Roles/Pages/CreateRole.php:7
* @route '/admin/roles/create'
*/
CreateRole.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: CreateRole.url(options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\Roles\Pages\CreateRole::__invoke
* @see app/Filament/Resources/Roles/Pages/CreateRole.php:7
* @route '/admin/roles/create'
*/
const CreateRoleForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: CreateRole.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Roles\Pages\CreateRole::__invoke
* @see app/Filament/Resources/Roles/Pages/CreateRole.php:7
* @route '/admin/roles/create'
*/
CreateRoleForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: CreateRole.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Roles\Pages\CreateRole::__invoke
* @see app/Filament/Resources/Roles/Pages/CreateRole.php:7
* @route '/admin/roles/create'
*/
CreateRoleForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: CreateRole.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

CreateRole.form = CreateRoleForm

export default CreateRole