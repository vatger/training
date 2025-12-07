import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\WaitingLists\Pages\CreateWaitingList::__invoke
* @see app/Filament/Resources/WaitingLists/Pages/CreateWaitingList.php:7
* @route '/admin/waiting-lists/create'
*/
const CreateWaitingList = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: CreateWaitingList.url(options),
    method: 'get',
})

CreateWaitingList.definition = {
    methods: ["get","head"],
    url: '/admin/waiting-lists/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\WaitingLists\Pages\CreateWaitingList::__invoke
* @see app/Filament/Resources/WaitingLists/Pages/CreateWaitingList.php:7
* @route '/admin/waiting-lists/create'
*/
CreateWaitingList.url = (options?: RouteQueryOptions) => {
    return CreateWaitingList.definition.url + queryParams(options)
}

/**
* @see \App\Filament\Resources\WaitingLists\Pages\CreateWaitingList::__invoke
* @see app/Filament/Resources/WaitingLists/Pages/CreateWaitingList.php:7
* @route '/admin/waiting-lists/create'
*/
CreateWaitingList.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: CreateWaitingList.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\WaitingLists\Pages\CreateWaitingList::__invoke
* @see app/Filament/Resources/WaitingLists/Pages/CreateWaitingList.php:7
* @route '/admin/waiting-lists/create'
*/
CreateWaitingList.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: CreateWaitingList.url(options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\WaitingLists\Pages\CreateWaitingList::__invoke
* @see app/Filament/Resources/WaitingLists/Pages/CreateWaitingList.php:7
* @route '/admin/waiting-lists/create'
*/
const CreateWaitingListForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: CreateWaitingList.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\WaitingLists\Pages\CreateWaitingList::__invoke
* @see app/Filament/Resources/WaitingLists/Pages/CreateWaitingList.php:7
* @route '/admin/waiting-lists/create'
*/
CreateWaitingListForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: CreateWaitingList.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\WaitingLists\Pages\CreateWaitingList::__invoke
* @see app/Filament/Resources/WaitingLists/Pages/CreateWaitingList.php:7
* @route '/admin/waiting-lists/create'
*/
CreateWaitingListForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: CreateWaitingList.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

CreateWaitingList.form = CreateWaitingListForm

export default CreateWaitingList