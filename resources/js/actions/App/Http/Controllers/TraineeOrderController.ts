import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\TraineeOrderController::updateOrder
* @see app/Http/Controllers/TraineeOrderController.php:14
* @route '/overview/update-trainee-order'
*/
export const updateOrder = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateOrder.url(options),
    method: 'post',
})

updateOrder.definition = {
    methods: ["post"],
    url: '/overview/update-trainee-order',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TraineeOrderController::updateOrder
* @see app/Http/Controllers/TraineeOrderController.php:14
* @route '/overview/update-trainee-order'
*/
updateOrder.url = (options?: RouteQueryOptions) => {
    return updateOrder.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TraineeOrderController::updateOrder
* @see app/Http/Controllers/TraineeOrderController.php:14
* @route '/overview/update-trainee-order'
*/
updateOrder.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateOrder.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TraineeOrderController::updateOrder
* @see app/Http/Controllers/TraineeOrderController.php:14
* @route '/overview/update-trainee-order'
*/
const updateOrderForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateOrder.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TraineeOrderController::updateOrder
* @see app/Http/Controllers/TraineeOrderController.php:14
* @route '/overview/update-trainee-order'
*/
updateOrderForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateOrder.url(options),
    method: 'post',
})

updateOrder.form = updateOrderForm

/**
* @see \App\Http\Controllers\TraineeOrderController::resetOrder
* @see app/Http/Controllers/TraineeOrderController.php:74
* @route '/overview/reset-trainee-order'
*/
export const resetOrder = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resetOrder.url(options),
    method: 'post',
})

resetOrder.definition = {
    methods: ["post"],
    url: '/overview/reset-trainee-order',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TraineeOrderController::resetOrder
* @see app/Http/Controllers/TraineeOrderController.php:74
* @route '/overview/reset-trainee-order'
*/
resetOrder.url = (options?: RouteQueryOptions) => {
    return resetOrder.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TraineeOrderController::resetOrder
* @see app/Http/Controllers/TraineeOrderController.php:74
* @route '/overview/reset-trainee-order'
*/
resetOrder.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resetOrder.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TraineeOrderController::resetOrder
* @see app/Http/Controllers/TraineeOrderController.php:74
* @route '/overview/reset-trainee-order'
*/
const resetOrderForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resetOrder.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TraineeOrderController::resetOrder
* @see app/Http/Controllers/TraineeOrderController.php:74
* @route '/overview/reset-trainee-order'
*/
resetOrderForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resetOrder.url(options),
    method: 'post',
})

resetOrder.form = resetOrderForm

const TraineeOrderController = { updateOrder, resetOrder }

export default TraineeOrderController