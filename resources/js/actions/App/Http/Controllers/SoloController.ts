import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\SoloController::addSolo
* @see app/Http/Controllers/SoloController.php:239
* @route '/overview/solo/add'
*/
export const addSolo = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: addSolo.url(options),
    method: 'post',
})

addSolo.definition = {
    methods: ["post"],
    url: '/overview/solo/add',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SoloController::addSolo
* @see app/Http/Controllers/SoloController.php:239
* @route '/overview/solo/add'
*/
addSolo.url = (options?: RouteQueryOptions) => {
    return addSolo.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SoloController::addSolo
* @see app/Http/Controllers/SoloController.php:239
* @route '/overview/solo/add'
*/
addSolo.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: addSolo.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SoloController::addSolo
* @see app/Http/Controllers/SoloController.php:239
* @route '/overview/solo/add'
*/
const addSoloForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: addSolo.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SoloController::addSolo
* @see app/Http/Controllers/SoloController.php:239
* @route '/overview/solo/add'
*/
addSoloForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: addSolo.url(options),
    method: 'post',
})

addSolo.form = addSoloForm

/**
* @see \App\Http\Controllers\SoloController::extendSolo
* @see app/Http/Controllers/SoloController.php:336
* @route '/overview/solo/extend'
*/
export const extendSolo = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: extendSolo.url(options),
    method: 'post',
})

extendSolo.definition = {
    methods: ["post"],
    url: '/overview/solo/extend',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SoloController::extendSolo
* @see app/Http/Controllers/SoloController.php:336
* @route '/overview/solo/extend'
*/
extendSolo.url = (options?: RouteQueryOptions) => {
    return extendSolo.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SoloController::extendSolo
* @see app/Http/Controllers/SoloController.php:336
* @route '/overview/solo/extend'
*/
extendSolo.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: extendSolo.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SoloController::extendSolo
* @see app/Http/Controllers/SoloController.php:336
* @route '/overview/solo/extend'
*/
const extendSoloForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: extendSolo.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SoloController::extendSolo
* @see app/Http/Controllers/SoloController.php:336
* @route '/overview/solo/extend'
*/
extendSoloForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: extendSolo.url(options),
    method: 'post',
})

extendSolo.form = extendSoloForm

/**
* @see \App\Http\Controllers\SoloController::removeSolo
* @see app/Http/Controllers/SoloController.php:419
* @route '/overview/solo/remove'
*/
export const removeSolo = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: removeSolo.url(options),
    method: 'post',
})

removeSolo.definition = {
    methods: ["post"],
    url: '/overview/solo/remove',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SoloController::removeSolo
* @see app/Http/Controllers/SoloController.php:419
* @route '/overview/solo/remove'
*/
removeSolo.url = (options?: RouteQueryOptions) => {
    return removeSolo.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SoloController::removeSolo
* @see app/Http/Controllers/SoloController.php:419
* @route '/overview/solo/remove'
*/
removeSolo.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: removeSolo.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SoloController::removeSolo
* @see app/Http/Controllers/SoloController.php:419
* @route '/overview/solo/remove'
*/
const removeSoloForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: removeSolo.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SoloController::removeSolo
* @see app/Http/Controllers/SoloController.php:419
* @route '/overview/solo/remove'
*/
removeSoloForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: removeSolo.url(options),
    method: 'post',
})

removeSolo.form = removeSoloForm

/**
* @see \App\Http\Controllers\SoloController::getSoloRequirements
* @see app/Http/Controllers/SoloController.php:136
* @route '/overview/solo/requirements'
*/
export const getSoloRequirements = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: getSoloRequirements.url(options),
    method: 'post',
})

getSoloRequirements.definition = {
    methods: ["post"],
    url: '/overview/solo/requirements',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SoloController::getSoloRequirements
* @see app/Http/Controllers/SoloController.php:136
* @route '/overview/solo/requirements'
*/
getSoloRequirements.url = (options?: RouteQueryOptions) => {
    return getSoloRequirements.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SoloController::getSoloRequirements
* @see app/Http/Controllers/SoloController.php:136
* @route '/overview/solo/requirements'
*/
getSoloRequirements.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: getSoloRequirements.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SoloController::getSoloRequirements
* @see app/Http/Controllers/SoloController.php:136
* @route '/overview/solo/requirements'
*/
const getSoloRequirementsForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: getSoloRequirements.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SoloController::getSoloRequirements
* @see app/Http/Controllers/SoloController.php:136
* @route '/overview/solo/requirements'
*/
getSoloRequirementsForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: getSoloRequirements.url(options),
    method: 'post',
})

getSoloRequirements.form = getSoloRequirementsForm

/**
* @see \App\Http\Controllers\SoloController::assignCoreTest
* @see app/Http/Controllers/SoloController.php:169
* @route '/overview/solo/assign-test'
*/
export const assignCoreTest = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: assignCoreTest.url(options),
    method: 'post',
})

assignCoreTest.definition = {
    methods: ["post"],
    url: '/overview/solo/assign-test',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\SoloController::assignCoreTest
* @see app/Http/Controllers/SoloController.php:169
* @route '/overview/solo/assign-test'
*/
assignCoreTest.url = (options?: RouteQueryOptions) => {
    return assignCoreTest.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SoloController::assignCoreTest
* @see app/Http/Controllers/SoloController.php:169
* @route '/overview/solo/assign-test'
*/
assignCoreTest.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: assignCoreTest.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SoloController::assignCoreTest
* @see app/Http/Controllers/SoloController.php:169
* @route '/overview/solo/assign-test'
*/
const assignCoreTestForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: assignCoreTest.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\SoloController::assignCoreTest
* @see app/Http/Controllers/SoloController.php:169
* @route '/overview/solo/assign-test'
*/
assignCoreTestForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: assignCoreTest.url(options),
    method: 'post',
})

assignCoreTest.form = assignCoreTestForm

const SoloController = { addSolo, extendSolo, removeSolo, getSoloRequirements, assignCoreTest }

export default SoloController