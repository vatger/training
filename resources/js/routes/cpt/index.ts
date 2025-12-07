import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
import log from './log'
import uploadFcda6e from './upload'
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
* @see \App\Http\Controllers\CptController::courseData
* @see app/Http/Controllers/CptController.php:162
* @route '/cpt/course-data'
*/
export const courseData = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: courseData.url(options),
    method: 'get',
})

courseData.definition = {
    methods: ["get","head"],
    url: '/cpt/course-data',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CptController::courseData
* @see app/Http/Controllers/CptController.php:162
* @route '/cpt/course-data'
*/
courseData.url = (options?: RouteQueryOptions) => {
    return courseData.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CptController::courseData
* @see app/Http/Controllers/CptController.php:162
* @route '/cpt/course-data'
*/
courseData.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: courseData.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CptController::courseData
* @see app/Http/Controllers/CptController.php:162
* @route '/cpt/course-data'
*/
courseData.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: courseData.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CptController::courseData
* @see app/Http/Controllers/CptController.php:162
* @route '/cpt/course-data'
*/
const courseDataForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: courseData.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CptController::courseData
* @see app/Http/Controllers/CptController.php:162
* @route '/cpt/course-data'
*/
courseDataForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: courseData.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CptController::courseData
* @see app/Http/Controllers/CptController.php:162
* @route '/cpt/course-data'
*/
courseDataForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: courseData.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

courseData.form = courseDataForm

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
* @see \App\Http\Controllers\CptController::upload
* @see app/Http/Controllers/CptController.php:360
* @route '/cpt/{cpt}/upload'
*/
export const upload = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: upload.url(args, options),
    method: 'get',
})

upload.definition = {
    methods: ["get","head"],
    url: '/cpt/{cpt}/upload',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CptController::upload
* @see app/Http/Controllers/CptController.php:360
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
* @see app/Http/Controllers/CptController.php:360
* @route '/cpt/{cpt}/upload'
*/
upload.get = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: upload.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CptController::upload
* @see app/Http/Controllers/CptController.php:360
* @route '/cpt/{cpt}/upload'
*/
upload.head = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: upload.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CptController::upload
* @see app/Http/Controllers/CptController.php:360
* @route '/cpt/{cpt}/upload'
*/
const uploadForm = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: upload.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CptController::upload
* @see app/Http/Controllers/CptController.php:360
* @route '/cpt/{cpt}/upload'
*/
uploadForm.get = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: upload.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CptController::upload
* @see app/Http/Controllers/CptController.php:360
* @route '/cpt/{cpt}/upload'
*/
uploadForm.head = (args: { cpt: number | { id: number } } | [cpt: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: upload.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
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

const cpt = {
    index: Object.assign(index, index),
    create: Object.assign(create, create),
    store: Object.assign(store, store),
    courseData: Object.assign(courseData, courseData),
    log: Object.assign(log, log),
    joinExaminer: Object.assign(joinExaminer, joinExaminer),
    leaveExaminer: Object.assign(leaveExaminer, leaveExaminer),
    joinLocal: Object.assign(joinLocal, joinLocal),
    leaveLocal: Object.assign(leaveLocal, leaveLocal),
    upload: Object.assign(upload, uploadFcda6e),
    destroy: Object.assign(destroy, destroy),
    grade: Object.assign(grade, grade),
}

export default cpt