import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\WaitingLists\Pages\ListWaitingLists::__invoke
* @see app/Filament/Resources/WaitingLists/Pages/ListWaitingLists.php:7
* @route '/admin/waiting-lists'
*/
const ListWaitingLists = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ListWaitingLists.url(options),
    method: 'get',
})

ListWaitingLists.definition = {
    methods: ["get","head"],
    url: '/admin/waiting-lists',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\WaitingLists\Pages\ListWaitingLists::__invoke
* @see app/Filament/Resources/WaitingLists/Pages/ListWaitingLists.php:7
* @route '/admin/waiting-lists'
*/
ListWaitingLists.url = (options?: RouteQueryOptions) => {
    return ListWaitingLists.definition.url + queryParams(options)
}

/**
* @see \App\Filament\Resources\WaitingLists\Pages\ListWaitingLists::__invoke
* @see app/Filament/Resources/WaitingLists/Pages/ListWaitingLists.php:7
* @route '/admin/waiting-lists'
*/
ListWaitingLists.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: ListWaitingLists.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\WaitingLists\Pages\ListWaitingLists::__invoke
* @see app/Filament/Resources/WaitingLists/Pages/ListWaitingLists.php:7
* @route '/admin/waiting-lists'
*/
ListWaitingLists.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: ListWaitingLists.url(options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\WaitingLists\Pages\ListWaitingLists::__invoke
* @see app/Filament/Resources/WaitingLists/Pages/ListWaitingLists.php:7
* @route '/admin/waiting-lists'
*/
const ListWaitingListsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListWaitingLists.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\WaitingLists\Pages\ListWaitingLists::__invoke
* @see app/Filament/Resources/WaitingLists/Pages/ListWaitingLists.php:7
* @route '/admin/waiting-lists'
*/
ListWaitingListsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListWaitingLists.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\WaitingLists\Pages\ListWaitingLists::__invoke
* @see app/Filament/Resources/WaitingLists/Pages/ListWaitingLists.php:7
* @route '/admin/waiting-lists'
*/
ListWaitingListsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: ListWaitingLists.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

ListWaitingLists.form = ListWaitingListsForm

export default ListWaitingLists