import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\WaitingLists\Pages\EditWaitingList::__invoke
* @see app/Filament/Resources/WaitingLists/Pages/EditWaitingList.php:7
* @route '/admin/waiting-lists/{record}/edit'
*/
const EditWaitingList = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: EditWaitingList.url(args, options),
    method: 'get',
})

EditWaitingList.definition = {
    methods: ["get","head"],
    url: '/admin/waiting-lists/{record}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\WaitingLists\Pages\EditWaitingList::__invoke
* @see app/Filament/Resources/WaitingLists/Pages/EditWaitingList.php:7
* @route '/admin/waiting-lists/{record}/edit'
*/
EditWaitingList.url = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return EditWaitingList.definition.url
            .replace('{record}', parsedArgs.record.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Filament\Resources\WaitingLists\Pages\EditWaitingList::__invoke
* @see app/Filament/Resources/WaitingLists/Pages/EditWaitingList.php:7
* @route '/admin/waiting-lists/{record}/edit'
*/
EditWaitingList.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: EditWaitingList.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\WaitingLists\Pages\EditWaitingList::__invoke
* @see app/Filament/Resources/WaitingLists/Pages/EditWaitingList.php:7
* @route '/admin/waiting-lists/{record}/edit'
*/
EditWaitingList.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: EditWaitingList.url(args, options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\WaitingLists\Pages\EditWaitingList::__invoke
* @see app/Filament/Resources/WaitingLists/Pages/EditWaitingList.php:7
* @route '/admin/waiting-lists/{record}/edit'
*/
const EditWaitingListForm = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EditWaitingList.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\WaitingLists\Pages\EditWaitingList::__invoke
* @see app/Filament/Resources/WaitingLists/Pages/EditWaitingList.php:7
* @route '/admin/waiting-lists/{record}/edit'
*/
EditWaitingListForm.get = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EditWaitingList.url(args, options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\WaitingLists\Pages\EditWaitingList::__invoke
* @see app/Filament/Resources/WaitingLists/Pages/EditWaitingList.php:7
* @route '/admin/waiting-lists/{record}/edit'
*/
EditWaitingListForm.head = (args: { record: string | number } | [record: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: EditWaitingList.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

EditWaitingList.form = EditWaitingListForm

export default EditWaitingList