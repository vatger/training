import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\Roles\Pages\ListRoles::__invoke
* @see app/Filament/Resources/Roles/Pages/ListRoles.php:7
* @route '/admin/roles'
*/
const ListRoles = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ListRoles.url(options),
    method: 'get',
})

ListRoles.definition = {
    methods: ["get","head"],
    url: '/admin/roles',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\Roles\Pages\ListRoles::__invoke
* @see app/Filament/Resources/Roles/Pages/ListRoles.php:7
* @route '/admin/roles'
*/
ListRoles.url = (options?: RouteQueryOptions) => {
    return ListRoles.definition.url + queryParams(options)
}

/**
* @see \App\Filament\Resources\Roles\Pages\ListRoles::__invoke
* @see app/Filament/Resources/Roles/Pages/ListRoles.php:7
* @route '/admin/roles'
*/
ListRoles.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ListRoles.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Roles\Pages\ListRoles::__invoke
* @see app/Filament/Resources/Roles/Pages/ListRoles.php:7
* @route '/admin/roles'
*/
ListRoles.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ListRoles.url(options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\Roles\Pages\ListRoles::__invoke
* @see app/Filament/Resources/Roles/Pages/ListRoles.php:7
* @route '/admin/roles'
*/
const ListRolesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListRoles.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Roles\Pages\ListRoles::__invoke
* @see app/Filament/Resources/Roles/Pages/ListRoles.php:7
* @route '/admin/roles'
*/
ListRolesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListRoles.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Roles\Pages\ListRoles::__invoke
* @see app/Filament/Resources/Roles/Pages/ListRoles.php:7
* @route '/admin/roles'
*/
ListRolesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListRoles.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

ListRoles.form = ListRolesForm

export default ListRoles