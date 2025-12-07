import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\TrainingLogController::index
* @see app/Http/Controllers/TrainingLogController.php:20
* @route '/training-logs'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/training-logs',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TrainingLogController::index
* @see app/Http/Controllers/TrainingLogController.php:20
* @route '/training-logs'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TrainingLogController::index
* @see app/Http/Controllers/TrainingLogController.php:20
* @route '/training-logs'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TrainingLogController::index
* @see app/Http/Controllers/TrainingLogController.php:20
* @route '/training-logs'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TrainingLogController::index
* @see app/Http/Controllers/TrainingLogController.php:20
* @route '/training-logs'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TrainingLogController::index
* @see app/Http/Controllers/TrainingLogController.php:20
* @route '/training-logs'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TrainingLogController::index
* @see app/Http/Controllers/TrainingLogController.php:20
* @route '/training-logs'
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
* @see \App\Http\Controllers\TrainingLogController::create
* @see app/Http/Controllers/TrainingLogController.php:63
* @route '/training-logs/create/{traineeId}/{courseId}'
*/
export const create = (args: { traineeId: string | number, courseId: string | number } | [traineeId: string | number, courseId: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(args, options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/training-logs/create/{traineeId}/{courseId}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TrainingLogController::create
* @see app/Http/Controllers/TrainingLogController.php:63
* @route '/training-logs/create/{traineeId}/{courseId}'
*/
create.url = (args: { traineeId: string | number, courseId: string | number } | [traineeId: string | number, courseId: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            traineeId: args[0],
            courseId: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        traineeId: args.traineeId,
        courseId: args.courseId,
    }

    return create.definition.url
            .replace('{traineeId}', parsedArgs.traineeId.toString())
            .replace('{courseId}', parsedArgs.courseId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TrainingLogController::create
* @see app/Http/Controllers/TrainingLogController.php:63
* @route '/training-logs/create/{traineeId}/{courseId}'
*/
create.get = (args: { traineeId: string | number, courseId: string | number } | [traineeId: string | number, courseId: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TrainingLogController::create
* @see app/Http/Controllers/TrainingLogController.php:63
* @route '/training-logs/create/{traineeId}/{courseId}'
*/
create.head = (args: { traineeId: string | number, courseId: string | number } | [traineeId: string | number, courseId: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TrainingLogController::create
* @see app/Http/Controllers/TrainingLogController.php:63
* @route '/training-logs/create/{traineeId}/{courseId}'
*/
const createForm = (args: { traineeId: string | number, courseId: string | number } | [traineeId: string | number, courseId: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TrainingLogController::create
* @see app/Http/Controllers/TrainingLogController.php:63
* @route '/training-logs/create/{traineeId}/{courseId}'
*/
createForm.get = (args: { traineeId: string | number, courseId: string | number } | [traineeId: string | number, courseId: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TrainingLogController::create
* @see app/Http/Controllers/TrainingLogController.php:63
* @route '/training-logs/create/{traineeId}/{courseId}'
*/
createForm.head = (args: { traineeId: string | number, courseId: string | number } | [traineeId: string | number, courseId: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

create.form = createForm

/**
* @see \App\Http\Controllers\TrainingLogController::view
* @see app/Http/Controllers/TrainingLogController.php:0
* @route '/training-logs/view/{traineeId}/{courseId}'
*/
export const view = (args: { traineeId: string | number, courseId: string | number } | [traineeId: string | number, courseId: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: view.url(args, options),
    method: 'get',
})

view.definition = {
    methods: ["get","head"],
    url: '/training-logs/view/{traineeId}/{courseId}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TrainingLogController::view
* @see app/Http/Controllers/TrainingLogController.php:0
* @route '/training-logs/view/{traineeId}/{courseId}'
*/
view.url = (args: { traineeId: string | number, courseId: string | number } | [traineeId: string | number, courseId: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            traineeId: args[0],
            courseId: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        traineeId: args.traineeId,
        courseId: args.courseId,
    }

    return view.definition.url
            .replace('{traineeId}', parsedArgs.traineeId.toString())
            .replace('{courseId}', parsedArgs.courseId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TrainingLogController::view
* @see app/Http/Controllers/TrainingLogController.php:0
* @route '/training-logs/view/{traineeId}/{courseId}'
*/
view.get = (args: { traineeId: string | number, courseId: string | number } | [traineeId: string | number, courseId: string | number ], options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: view.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TrainingLogController::view
* @see app/Http/Controllers/TrainingLogController.php:0
* @route '/training-logs/view/{traineeId}/{courseId}'
*/
view.head = (args: { traineeId: string | number, courseId: string | number } | [traineeId: string | number, courseId: string | number ], options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: view.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TrainingLogController::view
* @see app/Http/Controllers/TrainingLogController.php:0
* @route '/training-logs/view/{traineeId}/{courseId}'
*/
const viewForm = (args: { traineeId: string | number, courseId: string | number } | [traineeId: string | number, courseId: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: view.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TrainingLogController::view
* @see app/Http/Controllers/TrainingLogController.php:0
* @route '/training-logs/view/{traineeId}/{courseId}'
*/
viewForm.get = (args: { traineeId: string | number, courseId: string | number } | [traineeId: string | number, courseId: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: view.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TrainingLogController::view
* @see app/Http/Controllers/TrainingLogController.php:0
* @route '/training-logs/view/{traineeId}/{courseId}'
*/
viewForm.head = (args: { traineeId: string | number, courseId: string | number } | [traineeId: string | number, courseId: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\TrainingLogController::store
* @see app/Http/Controllers/TrainingLogController.php:105
* @route '/training-logs'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/training-logs',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TrainingLogController::store
* @see app/Http/Controllers/TrainingLogController.php:105
* @route '/training-logs'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TrainingLogController::store
* @see app/Http/Controllers/TrainingLogController.php:105
* @route '/training-logs'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TrainingLogController::store
* @see app/Http/Controllers/TrainingLogController.php:105
* @route '/training-logs'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TrainingLogController::store
* @see app/Http/Controllers/TrainingLogController.php:105
* @route '/training-logs'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\TrainingLogController::show
* @see app/Http/Controllers/TrainingLogController.php:242
* @route '/training-logs/{id}'
*/
export const show = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/training-logs/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TrainingLogController::show
* @see app/Http/Controllers/TrainingLogController.php:242
* @route '/training-logs/{id}'
*/
show.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return show.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TrainingLogController::show
* @see app/Http/Controllers/TrainingLogController.php:242
* @route '/training-logs/{id}'
*/
show.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TrainingLogController::show
* @see app/Http/Controllers/TrainingLogController.php:242
* @route '/training-logs/{id}'
*/
show.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TrainingLogController::show
* @see app/Http/Controllers/TrainingLogController.php:242
* @route '/training-logs/{id}'
*/
const showForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TrainingLogController::show
* @see app/Http/Controllers/TrainingLogController.php:242
* @route '/training-logs/{id}'
*/
showForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TrainingLogController::show
* @see app/Http/Controllers/TrainingLogController.php:242
* @route '/training-logs/{id}'
*/
showForm.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\TrainingLogController::edit
* @see app/Http/Controllers/TrainingLogController.php:267
* @route '/training-logs/{id}/edit'
*/
export const edit = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/training-logs/{id}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TrainingLogController::edit
* @see app/Http/Controllers/TrainingLogController.php:267
* @route '/training-logs/{id}/edit'
*/
edit.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return edit.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TrainingLogController::edit
* @see app/Http/Controllers/TrainingLogController.php:267
* @route '/training-logs/{id}/edit'
*/
edit.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TrainingLogController::edit
* @see app/Http/Controllers/TrainingLogController.php:267
* @route '/training-logs/{id}/edit'
*/
edit.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TrainingLogController::edit
* @see app/Http/Controllers/TrainingLogController.php:267
* @route '/training-logs/{id}/edit'
*/
const editForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TrainingLogController::edit
* @see app/Http/Controllers/TrainingLogController.php:267
* @route '/training-logs/{id}/edit'
*/
editForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TrainingLogController::edit
* @see app/Http/Controllers/TrainingLogController.php:267
* @route '/training-logs/{id}/edit'
*/
editForm.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

edit.form = editForm

/**
* @see \App\Http\Controllers\TrainingLogController::update
* @see app/Http/Controllers/TrainingLogController.php:307
* @route '/training-logs/{id}'
*/
export const update = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/training-logs/{id}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\TrainingLogController::update
* @see app/Http/Controllers/TrainingLogController.php:307
* @route '/training-logs/{id}'
*/
update.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return update.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TrainingLogController::update
* @see app/Http/Controllers/TrainingLogController.php:307
* @route '/training-logs/{id}'
*/
update.put = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\TrainingLogController::update
* @see app/Http/Controllers/TrainingLogController.php:307
* @route '/training-logs/{id}'
*/
const updateForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TrainingLogController::update
* @see app/Http/Controllers/TrainingLogController.php:307
* @route '/training-logs/{id}'
*/
updateForm.put = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

/**
* @see \App\Http\Controllers\TrainingLogController::destroy
* @see app/Http/Controllers/TrainingLogController.php:426
* @route '/training-logs/{id}'
*/
export const destroy = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/training-logs/{id}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\TrainingLogController::destroy
* @see app/Http/Controllers/TrainingLogController.php:426
* @route '/training-logs/{id}'
*/
destroy.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    if (Array.isArray(args)) {
        args = {
            id: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        id: args.id,
    }

    return destroy.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TrainingLogController::destroy
* @see app/Http/Controllers/TrainingLogController.php:426
* @route '/training-logs/{id}'
*/
destroy.delete = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\TrainingLogController::destroy
* @see app/Http/Controllers/TrainingLogController.php:426
* @route '/training-logs/{id}'
*/
const destroyForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TrainingLogController::destroy
* @see app/Http/Controllers/TrainingLogController.php:426
* @route '/training-logs/{id}'
*/
destroyForm.delete = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

const trainingLogs = {
    index: Object.assign(index, index),
    create: Object.assign(create, create),
    view: Object.assign(view, view),
    store: Object.assign(store, store),
    show: Object.assign(show, show),
    edit: Object.assign(edit, edit),
    update: Object.assign(update, update),
    destroy: Object.assign(destroy, destroy),
}

export default trainingLogs