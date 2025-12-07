import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Filament\Resources\Examiners\Pages\CreateExaminer::__invoke
* @see app/Filament/Resources/Examiners/Pages/CreateExaminer.php:7
* @route '/admin/examiners/create'
*/
const CreateExaminer = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: CreateExaminer.url(options),
    method: 'get',
})

CreateExaminer.definition = {
    methods: ["get","head"],
    url: '/admin/examiners/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Filament\Resources\Examiners\Pages\CreateExaminer::__invoke
* @see app/Filament/Resources/Examiners/Pages/CreateExaminer.php:7
* @route '/admin/examiners/create'
*/
CreateExaminer.url = (options?: RouteQueryOptions) => {
    return CreateExaminer.definition.url + queryParams(options)
}

/**
* @see \App\Filament\Resources\Examiners\Pages\CreateExaminer::__invoke
* @see app/Filament/Resources/Examiners/Pages/CreateExaminer.php:7
* @route '/admin/examiners/create'
*/
CreateExaminer.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: CreateExaminer.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Examiners\Pages\CreateExaminer::__invoke
* @see app/Filament/Resources/Examiners/Pages/CreateExaminer.php:7
* @route '/admin/examiners/create'
*/
CreateExaminer.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: CreateExaminer.url(options),
    method: 'head',
})

/**
* @see \App\Filament\Resources\Examiners\Pages\CreateExaminer::__invoke
* @see app/Filament/Resources/Examiners/Pages/CreateExaminer.php:7
* @route '/admin/examiners/create'
*/
const CreateExaminerForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: CreateExaminer.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Examiners\Pages\CreateExaminer::__invoke
* @see app/Filament/Resources/Examiners/Pages/CreateExaminer.php:7
* @route '/admin/examiners/create'
*/
CreateExaminerForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: CreateExaminer.url(options),
    method: 'get',
})

/**
* @see \App\Filament\Resources\Examiners\Pages\CreateExaminer::__invoke
* @see app/Filament/Resources/Examiners/Pages/CreateExaminer.php:7
* @route '/admin/examiners/create'
*/
CreateExaminerForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: CreateExaminer.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

CreateExaminer.form = CreateExaminerForm

export default CreateExaminer