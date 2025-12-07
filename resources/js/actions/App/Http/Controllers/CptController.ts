import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\CptController::index
* @see app/Http/Controllers/CptController.php:18
* @route '/cpt'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/cpt',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CptController::index
* @see app/Http/Controllers/CptController.php:18
* @route '/cpt'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CptController::index
* @see app/Http/Controllers/CptController.php:18
* @route '/cpt'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CptController::index
* @see app/Http/Controllers/CptController.php:18
* @route '/cpt'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CptController::index
* @see app/Http/Controllers/CptController.php:18
* @route '/cpt'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CptController::index
* @see app/Http/Controllers/CptController.php:18
* @route '/cpt'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CptController::index
* @see app/Http/Controllers/CptController.php:18
* @route '/cpt'
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
* @see \App\Http\Controllers\CptController::create
* @see app/Http/Controllers/CptController.php:76
* @route '/cpt/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/cpt/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CptController::create
* @see app/Http/Controllers/CptController.php:76
* @route '/cpt/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CptController::create
* @see app/Http/Controllers/CptController.php:76
* @route '/cpt/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CptController::create
* @see app/Http/Controllers/CptController.php:76
* @route '/cpt/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CptController::create
* @see app/Http/Controllers/CptController.php:76
* @route '/cpt/create'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CptController::create
* @see app/Http/Controllers/CptController.php:76
* @route '/cpt/create'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CptController::create
* @see app/Http/Controllers/CptController.php:76
* @route '/cpt/create'
*/
createForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

create.form = createForm

/**
* @see \App\Http\Controllers\CptController::store
* @see app/Http/Controllers/CptController.php:96
* @route '/cpt'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/cpt',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CptController::store
* @see app/Http/Controllers/CptController.php:96
* @route '/cpt'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CptController::store
* @see app/Http/Controllers/CptController.php:96
* @route '/cpt'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CptController::store
* @see app/Http/Controllers/CptController.php:96
* @route '/cpt'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CptController::store
* @see app/Http/Controllers/CptController.php:96
* @route '/cpt'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\CptController::getCourseData
* @see app/Http/Controllers/CptController.php:162
* @route '/cpt/course-data'
*/
export const getCourseData = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getCourseData.url(options),
    method: 'get',
})

getCourseData.definition = {
    methods: ["get","head"],
    url: '/cpt/course-data',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CptController::getCourseData
* @see app/Http/Controllers/CptController.php:162
* @route '/cpt/course-data'
*/
getCourseData.url = (options?: RouteQueryOptions) => {
    return getCourseData.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CptController::getCourseData
* @see app/Http/Controllers/CptController.php:162
* @route '/cpt/course-data'
*/
getCourseData.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getCourseData.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CptController::getCourseData
* @see app/Http/Controllers/CptController.php:162
* @route '/cpt/course-data'
*/
getCourseData.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getCourseData.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CptController::getCourseData
* @see app/Http/Controllers/CptController.php:162
* @route '/cpt/course-data'
*/
const getCourseDataForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getCourseData.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CptController::getCourseData
* @see app/Http/Controllers/CptController.php:162
* @route '/cpt/course-data'
*/
getCourseDataForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getCourseData.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CptController::getCourseData
* @see app/Http/Controllers/CptController.php:162
* @route '/cpt/course-data'
*/
getCourseDataForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getCourseData.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

getCourseData.form = getCourseDataForm

/**
* @see \App\Http\Controllers\CptController::viewLog
* @see app/Http/Controllers/CptController.php:489
* @route '/cpt/log/{log}'
*/
export const viewLog = (args: { log: number | { id: number } } | [log: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: viewLog.url(args, options),
    method: 'get',
})

viewLog.definition = {
    methods: ["get","head"],
    url: '/cpt/log/{log}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CptController::viewLog
* @see app/Http/Controllers/CptController.php:489
* @route '/cpt/log/{log}'
*/
viewLog.url = (args: { log: number | { id: number } } | [log: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { log: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { log: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            log: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        log: typeof args.log === 'object'
        ? args.log.id
        : args.log,
    }

    return viewLog.definition.url
            .replace('{log}', parsedArgs.log.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CptController::viewLog
* @see app/Http/Controllers/CptController.php:489
* @route '/cpt/log/{log}'
*/
viewLog.get = (args: { log: number | { id: number } } | [log: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: viewLog.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CptController::viewLog
* @see app/Http/Controllers/CptController.php:489
* @route '/cpt/log/{log}'
*/
viewLog.head = (args: { log: number | { id: number } } | [log: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: viewLog.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CptController::viewLog
* @see app/Http/Controllers/CptController.php:489
* @route '/cpt/log/{log}'
*/
const viewLogForm = (args: { log: number | { id: number } } | [log: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: viewLog.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CptController::viewLog
* @see app/Http/Controllers/CptController.php:489
* @route '/cpt/log/{log}'
*/
viewLogForm.get = (args: { log: number | { id: number } } | [log: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: viewLog.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CptController::viewLog
* @see app/Http/Controllers/CptController.php:489
* @route '/cpt/log/{log}'
*/
viewLogForm.head = (args: { log: number | { id: number } } | [log: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: viewLog.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

viewLog.form = viewLogForm

/**
* @see \App\Http\Controllers\CptController::joinExaminer
* @see app/Http/Controllers/CptController.php:224
* @route '/cpt/{cpt}/join-examiner'
*/
export const joinExaminer = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: joinExaminer.url(args, options),
    method: 'post',
})

joinExaminer.definition = {
    methods: ["post"],
    url: '/cpt/{cpt}/join-examiner',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CptController::joinExaminer
* @see app/Http/Controllers/CptController.php:224
* @route '/cpt/{cpt}/join-examiner'
*/
joinExaminer.url = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { cpt: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { cpt: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            cpt: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        cpt: typeof args.cpt === 'object'
        ? args.cpt.id
        : args.cpt,
    }

    return joinExaminer.definition.url
            .replace('{cpt}', parsedArgs.cpt.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CptController::joinExaminer
* @see app/Http/Controllers/CptController.php:224
* @route '/cpt/{cpt}/join-examiner'
*/
joinExaminer.post = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: joinExaminer.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CptController::joinExaminer
* @see app/Http/Controllers/CptController.php:224
* @route '/cpt/{cpt}/join-examiner'
*/
const joinExaminerForm = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: joinExaminer.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CptController::joinExaminer
* @see app/Http/Controllers/CptController.php:224
* @route '/cpt/{cpt}/join-examiner'
*/
joinExaminerForm.post = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: joinExaminer.url(args, options),
    method: 'post',
})

joinExaminer.form = joinExaminerForm

/**
* @see \App\Http\Controllers\CptController::leaveExaminer
* @see app/Http/Controllers/CptController.php:264
* @route '/cpt/{cpt}/leave-examiner'
*/
export const leaveExaminer = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: leaveExaminer.url(args, options),
    method: 'post',
})

leaveExaminer.definition = {
    methods: ["post"],
    url: '/cpt/{cpt}/leave-examiner',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CptController::leaveExaminer
* @see app/Http/Controllers/CptController.php:264
* @route '/cpt/{cpt}/leave-examiner'
*/
leaveExaminer.url = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { cpt: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { cpt: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            cpt: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        cpt: typeof args.cpt === 'object'
        ? args.cpt.id
        : args.cpt,
    }

    return leaveExaminer.definition.url
            .replace('{cpt}', parsedArgs.cpt.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CptController::leaveExaminer
* @see app/Http/Controllers/CptController.php:264
* @route '/cpt/{cpt}/leave-examiner'
*/
leaveExaminer.post = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: leaveExaminer.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CptController::leaveExaminer
* @see app/Http/Controllers/CptController.php:264
* @route '/cpt/{cpt}/leave-examiner'
*/
const leaveExaminerForm = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: leaveExaminer.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CptController::leaveExaminer
* @see app/Http/Controllers/CptController.php:264
* @route '/cpt/{cpt}/leave-examiner'
*/
leaveExaminerForm.post = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: leaveExaminer.url(args, options),
    method: 'post',
})

leaveExaminer.form = leaveExaminerForm

/**
* @see \App\Http\Controllers\CptController::joinLocal
* @see app/Http/Controllers/CptController.php:285
* @route '/cpt/{cpt}/join-local'
*/
export const joinLocal = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: joinLocal.url(args, options),
    method: 'post',
})

joinLocal.definition = {
    methods: ["post"],
    url: '/cpt/{cpt}/join-local',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CptController::joinLocal
* @see app/Http/Controllers/CptController.php:285
* @route '/cpt/{cpt}/join-local'
*/
joinLocal.url = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { cpt: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { cpt: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            cpt: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        cpt: typeof args.cpt === 'object'
        ? args.cpt.id
        : args.cpt,
    }

    return joinLocal.definition.url
            .replace('{cpt}', parsedArgs.cpt.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CptController::joinLocal
* @see app/Http/Controllers/CptController.php:285
* @route '/cpt/{cpt}/join-local'
*/
joinLocal.post = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: joinLocal.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CptController::joinLocal
* @see app/Http/Controllers/CptController.php:285
* @route '/cpt/{cpt}/join-local'
*/
const joinLocalForm = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: joinLocal.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CptController::joinLocal
* @see app/Http/Controllers/CptController.php:285
* @route '/cpt/{cpt}/join-local'
*/
joinLocalForm.post = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: joinLocal.url(args, options),
    method: 'post',
})

joinLocal.form = joinLocalForm

/**
* @see \App\Http\Controllers\CptController::leaveLocal
* @see app/Http/Controllers/CptController.php:314
* @route '/cpt/{cpt}/leave-local'
*/
export const leaveLocal = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: leaveLocal.url(args, options),
    method: 'post',
})

leaveLocal.definition = {
    methods: ["post"],
    url: '/cpt/{cpt}/leave-local',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CptController::leaveLocal
* @see app/Http/Controllers/CptController.php:314
* @route '/cpt/{cpt}/leave-local'
*/
leaveLocal.url = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { cpt: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { cpt: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            cpt: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        cpt: typeof args.cpt === 'object'
        ? args.cpt.id
        : args.cpt,
    }

    return leaveLocal.definition.url
            .replace('{cpt}', parsedArgs.cpt.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CptController::leaveLocal
* @see app/Http/Controllers/CptController.php:314
* @route '/cpt/{cpt}/leave-local'
*/
leaveLocal.post = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: leaveLocal.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CptController::leaveLocal
* @see app/Http/Controllers/CptController.php:314
* @route '/cpt/{cpt}/leave-local'
*/
const leaveLocalForm = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: leaveLocal.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CptController::leaveLocal
* @see app/Http/Controllers/CptController.php:314
* @route '/cpt/{cpt}/leave-local'
*/
leaveLocalForm.post = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: leaveLocal.url(args, options),
    method: 'post',
})

leaveLocal.form = leaveLocalForm

/**
* @see \App\Http\Controllers\CptController::uploadPage
* @see app/Http/Controllers/CptController.php:360
* @route '/cpt/{cpt}/upload'
*/
export const uploadPage = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: uploadPage.url(args, options),
    method: 'get',
})

uploadPage.definition = {
    methods: ["get","head"],
    url: '/cpt/{cpt}/upload',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CptController::uploadPage
* @see app/Http/Controllers/CptController.php:360
* @route '/cpt/{cpt}/upload'
*/
uploadPage.url = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { cpt: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { cpt: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            cpt: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        cpt: typeof args.cpt === 'object'
        ? args.cpt.id
        : args.cpt,
    }

    return uploadPage.definition.url
            .replace('{cpt}', parsedArgs.cpt.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CptController::uploadPage
* @see app/Http/Controllers/CptController.php:360
* @route '/cpt/{cpt}/upload'
*/
uploadPage.get = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: uploadPage.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CptController::uploadPage
* @see app/Http/Controllers/CptController.php:360
* @route '/cpt/{cpt}/upload'
*/
uploadPage.head = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: uploadPage.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CptController::uploadPage
* @see app/Http/Controllers/CptController.php:360
* @route '/cpt/{cpt}/upload'
*/
const uploadPageForm = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: uploadPage.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CptController::uploadPage
* @see app/Http/Controllers/CptController.php:360
* @route '/cpt/{cpt}/upload'
*/
uploadPageForm.get = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: uploadPage.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CptController::uploadPage
* @see app/Http/Controllers/CptController.php:360
* @route '/cpt/{cpt}/upload'
*/
uploadPageForm.head = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: uploadPage.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

uploadPage.form = uploadPageForm

/**
* @see \App\Http\Controllers\CptController::upload
* @see app/Http/Controllers/CptController.php:427
* @route '/cpt/{cpt}/upload'
*/
export const upload = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: upload.url(args, options),
    method: 'post',
})

upload.definition = {
    methods: ["post"],
    url: '/cpt/{cpt}/upload',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CptController::upload
* @see app/Http/Controllers/CptController.php:427
* @route '/cpt/{cpt}/upload'
*/
upload.url = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { cpt: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { cpt: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            cpt: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        cpt: typeof args.cpt === 'object'
        ? args.cpt.id
        : args.cpt,
    }

    return upload.definition.url
            .replace('{cpt}', parsedArgs.cpt.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CptController::upload
* @see app/Http/Controllers/CptController.php:427
* @route '/cpt/{cpt}/upload'
*/
upload.post = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: upload.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CptController::upload
* @see app/Http/Controllers/CptController.php:427
* @route '/cpt/{cpt}/upload'
*/
const uploadForm = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: upload.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CptController::upload
* @see app/Http/Controllers/CptController.php:427
* @route '/cpt/{cpt}/upload'
*/
uploadForm.post = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: upload.url(args, options),
    method: 'post',
})

upload.form = uploadForm

/**
* @see \App\Http\Controllers\CptController::destroy
* @see app/Http/Controllers/CptController.php:335
* @route '/cpt/{cpt}'
*/
export const destroy = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/cpt/{cpt}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\CptController::destroy
* @see app/Http/Controllers/CptController.php:335
* @route '/cpt/{cpt}'
*/
destroy.url = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { cpt: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { cpt: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            cpt: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        cpt: typeof args.cpt === 'object'
        ? args.cpt.id
        : args.cpt,
    }

    return destroy.definition.url
            .replace('{cpt}', parsedArgs.cpt.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CptController::destroy
* @see app/Http/Controllers/CptController.php:335
* @route '/cpt/{cpt}'
*/
destroy.delete = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\CptController::destroy
* @see app/Http/Controllers/CptController.php:335
* @route '/cpt/{cpt}'
*/
const destroyForm = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CptController::destroy
* @see app/Http/Controllers/CptController.php:335
* @route '/cpt/{cpt}'
*/
destroyForm.delete = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

/**
* @see \App\Http\Controllers\CptController::grade
* @see app/Http/Controllers/CptController.php:462
* @route '/cpt/{cpt}/grade/{result}'
*/
export const grade = (args: { cpt: number | { id: number }, result: string | number } | [cpt: number | { id: number }, result: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: grade.url(args, options),
    method: 'post',
})

grade.definition = {
    methods: ["post"],
    url: '/cpt/{cpt}/grade/{result}',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CptController::grade
* @see app/Http/Controllers/CptController.php:462
* @route '/cpt/{cpt}/grade/{result}'
*/
grade.url = (args: { cpt: number | { id: number }, result: string | number } | [cpt: number | { id: number }, result: string | number ], options?: RouteQueryOptions) => {
    if (Array.isArray(args)) {
        args = {
            cpt: args[0],
            result: args[1],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        cpt: typeof args.cpt === 'object'
        ? args.cpt.id
        : args.cpt,
        result: args.result,
    }

    return grade.definition.url
            .replace('{cpt}', parsedArgs.cpt.toString())
            .replace('{result}', parsedArgs.result.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CptController::grade
* @see app/Http/Controllers/CptController.php:462
* @route '/cpt/{cpt}/grade/{result}'
*/
grade.post = (args: { cpt: number | { id: number }, result: string | number } | [cpt: number | { id: number }, result: string | number ], options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: grade.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CptController::grade
* @see app/Http/Controllers/CptController.php:462
* @route '/cpt/{cpt}/grade/{result}'
*/
const gradeForm = (args: { cpt: number | { id: number }, result: string | number } | [cpt: number | { id: number }, result: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: grade.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CptController::grade
* @see app/Http/Controllers/CptController.php:462
* @route '/cpt/{cpt}/grade/{result}'
*/
gradeForm.post = (args: { cpt: number | { id: number }, result: string | number } | [cpt: number | { id: number }, result: string | number ], options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: grade.url(args, options),
    method: 'post',
})

grade.form = gradeForm

const CptController = { index, create, store, getCourseData, viewLog, joinExaminer, leaveExaminer, joinLocal, leaveLocal, uploadPage, upload, destroy, grade }

export default CptController