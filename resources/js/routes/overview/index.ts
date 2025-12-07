import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
import course from './course'
/**
* @see \App\Http\Controllers\MentorOverviewController::getCourseMentors
* @see app/Http/Controllers/MentorOverviewController.php:440
* @route '/course/{course}/mentors'
*/
export const getCourseMentors = (args: { course: string | number } | [course: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getCourseMentors.url(args, options),
    method: 'get',
})

getCourseMentors.definition = {
    methods: ["get","head"],
    url: '/course/{course}/mentors',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MentorOverviewController::getCourseMentors
* @see app/Http/Controllers/MentorOverviewController.php:440
* @route '/course/{course}/mentors'
*/
getCourseMentors.url = (args: { course: string | number } | [course: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { course: args }
    }

    if (Array.isArray(args)) {
        args = {
            course: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        course: args.course,
    }

    return getCourseMentors.definition.url
            .replace('{course}', parsedArgs.course.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MentorOverviewController::getCourseMentors
* @see app/Http/Controllers/MentorOverviewController.php:440
* @route '/course/{course}/mentors'
*/
getCourseMentors.get = (args: { course: string | number } | [course: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getCourseMentors.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::getCourseMentors
* @see app/Http/Controllers/MentorOverviewController.php:440
* @route '/course/{course}/mentors'
*/
getCourseMentors.head = (args: { course: string | number } | [course: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getCourseMentors.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::getCourseMentors
* @see app/Http/Controllers/MentorOverviewController.php:440
* @route '/course/{course}/mentors'
*/
const getCourseMentorsForm = (args: { course: string | number } | [course: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getCourseMentors.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::getCourseMentors
* @see app/Http/Controllers/MentorOverviewController.php:440
* @route '/course/{course}/mentors'
*/
getCourseMentorsForm.get = (args: { course: string | number } | [course: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getCourseMentors.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::getCourseMentors
* @see app/Http/Controllers/MentorOverviewController.php:440
* @route '/course/{course}/mentors'
*/
getCourseMentorsForm.head = (args: { course: string | number } | [course: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getCourseMentors.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

getCourseMentors.form = getCourseMentorsForm

/**
* @see \App\Http\Controllers\MentorOverviewController::index
* @see app/Http/Controllers/MentorOverviewController.php:15
* @route '/overview'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/overview',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MentorOverviewController::index
* @see app/Http/Controllers/MentorOverviewController.php:15
* @route '/overview'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MentorOverviewController::index
* @see app/Http/Controllers/MentorOverviewController.php:15
* @route '/overview'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::index
* @see app/Http/Controllers/MentorOverviewController.php:15
* @route '/overview'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::index
* @see app/Http/Controllers/MentorOverviewController.php:15
* @route '/overview'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::index
* @see app/Http/Controllers/MentorOverviewController.php:15
* @route '/overview'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::index
* @see app/Http/Controllers/MentorOverviewController.php:15
* @route '/overview'
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
* @see \App\Http\Controllers\MentorOverviewController::updateRemark
* @see app/Http/Controllers/MentorOverviewController.php:468
* @route '/overview/update-remark'
*/
export const updateRemark = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateRemark.url(options),
    method: 'post',
})

updateRemark.definition = {
    methods: ["post"],
    url: '/overview/update-remark',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MentorOverviewController::updateRemark
* @see app/Http/Controllers/MentorOverviewController.php:468
* @route '/overview/update-remark'
*/
updateRemark.url = (options?: RouteQueryOptions) => {
    return updateRemark.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MentorOverviewController::updateRemark
* @see app/Http/Controllers/MentorOverviewController.php:468
* @route '/overview/update-remark'
*/
updateRemark.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateRemark.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::updateRemark
* @see app/Http/Controllers/MentorOverviewController.php:468
* @route '/overview/update-remark'
*/
const updateRemarkForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateRemark.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::updateRemark
* @see app/Http/Controllers/MentorOverviewController.php:468
* @route '/overview/update-remark'
*/
updateRemarkForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateRemark.url(options),
    method: 'post',
})

updateRemark.form = updateRemarkForm

/**
* @see \App\Http\Controllers\MentorOverviewController::removeTrainee
* @see app/Http/Controllers/MentorOverviewController.php:517
* @route '/overview/remove-trainee'
*/
export const removeTrainee = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: removeTrainee.url(options),
    method: 'post',
})

removeTrainee.definition = {
    methods: ["post"],
    url: '/overview/remove-trainee',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MentorOverviewController::removeTrainee
* @see app/Http/Controllers/MentorOverviewController.php:517
* @route '/overview/remove-trainee'
*/
removeTrainee.url = (options?: RouteQueryOptions) => {
    return removeTrainee.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MentorOverviewController::removeTrainee
* @see app/Http/Controllers/MentorOverviewController.php:517
* @route '/overview/remove-trainee'
*/
removeTrainee.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: removeTrainee.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::removeTrainee
* @see app/Http/Controllers/MentorOverviewController.php:517
* @route '/overview/remove-trainee'
*/
const removeTraineeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: removeTrainee.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::removeTrainee
* @see app/Http/Controllers/MentorOverviewController.php:517
* @route '/overview/remove-trainee'
*/
removeTraineeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: removeTrainee.url(options),
    method: 'post',
})

removeTrainee.form = removeTraineeForm

/**
* @see \App\Http\Controllers\MentorOverviewController::finishTrainee
* @see app/Http/Controllers/MentorOverviewController.php:1044
* @route '/overview/finish-trainee'
*/
export const finishTrainee = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: finishTrainee.url(options),
    method: 'post',
})

finishTrainee.definition = {
    methods: ["post"],
    url: '/overview/finish-trainee',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MentorOverviewController::finishTrainee
* @see app/Http/Controllers/MentorOverviewController.php:1044
* @route '/overview/finish-trainee'
*/
finishTrainee.url = (options?: RouteQueryOptions) => {
    return finishTrainee.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MentorOverviewController::finishTrainee
* @see app/Http/Controllers/MentorOverviewController.php:1044
* @route '/overview/finish-trainee'
*/
finishTrainee.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: finishTrainee.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::finishTrainee
* @see app/Http/Controllers/MentorOverviewController.php:1044
* @route '/overview/finish-trainee'
*/
const finishTraineeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: finishTrainee.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::finishTrainee
* @see app/Http/Controllers/MentorOverviewController.php:1044
* @route '/overview/finish-trainee'
*/
finishTraineeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: finishTrainee.url(options),
    method: 'post',
})

finishTrainee.form = finishTraineeForm

/**
* @see \App\Http\Controllers\MentorOverviewController::claimTrainee
* @see app/Http/Controllers/MentorOverviewController.php:558
* @route '/overview/claim-trainee'
*/
export const claimTrainee = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: claimTrainee.url(options),
    method: 'post',
})

claimTrainee.definition = {
    methods: ["post"],
    url: '/overview/claim-trainee',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MentorOverviewController::claimTrainee
* @see app/Http/Controllers/MentorOverviewController.php:558
* @route '/overview/claim-trainee'
*/
claimTrainee.url = (options?: RouteQueryOptions) => {
    return claimTrainee.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MentorOverviewController::claimTrainee
* @see app/Http/Controllers/MentorOverviewController.php:558
* @route '/overview/claim-trainee'
*/
claimTrainee.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: claimTrainee.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::claimTrainee
* @see app/Http/Controllers/MentorOverviewController.php:558
* @route '/overview/claim-trainee'
*/
const claimTraineeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: claimTrainee.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::claimTrainee
* @see app/Http/Controllers/MentorOverviewController.php:558
* @route '/overview/claim-trainee'
*/
claimTraineeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: claimTrainee.url(options),
    method: 'post',
})

claimTrainee.form = claimTraineeForm

/**
* @see \App\Http\Controllers\MentorOverviewController::unclaimTrainee
* @see app/Http/Controllers/MentorOverviewController.php:677
* @route '/overview/unclaim-trainee'
*/
export const unclaimTrainee = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: unclaimTrainee.url(options),
    method: 'post',
})

unclaimTrainee.definition = {
    methods: ["post"],
    url: '/overview/unclaim-trainee',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MentorOverviewController::unclaimTrainee
* @see app/Http/Controllers/MentorOverviewController.php:677
* @route '/overview/unclaim-trainee'
*/
unclaimTrainee.url = (options?: RouteQueryOptions) => {
    return unclaimTrainee.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MentorOverviewController::unclaimTrainee
* @see app/Http/Controllers/MentorOverviewController.php:677
* @route '/overview/unclaim-trainee'
*/
unclaimTrainee.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: unclaimTrainee.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::unclaimTrainee
* @see app/Http/Controllers/MentorOverviewController.php:677
* @route '/overview/unclaim-trainee'
*/
const unclaimTraineeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: unclaimTrainee.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::unclaimTrainee
* @see app/Http/Controllers/MentorOverviewController.php:677
* @route '/overview/unclaim-trainee'
*/
unclaimTraineeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: unclaimTrainee.url(options),
    method: 'post',
})

unclaimTrainee.form = unclaimTraineeForm

/**
* @see \App\Http\Controllers\MentorOverviewController::assignTrainee
* @see app/Http/Controllers/MentorOverviewController.php:614
* @route '/overview/assign-trainee'
*/
export const assignTrainee = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: assignTrainee.url(options),
    method: 'post',
})

assignTrainee.definition = {
    methods: ["post"],
    url: '/overview/assign-trainee',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MentorOverviewController::assignTrainee
* @see app/Http/Controllers/MentorOverviewController.php:614
* @route '/overview/assign-trainee'
*/
assignTrainee.url = (options?: RouteQueryOptions) => {
    return assignTrainee.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MentorOverviewController::assignTrainee
* @see app/Http/Controllers/MentorOverviewController.php:614
* @route '/overview/assign-trainee'
*/
assignTrainee.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: assignTrainee.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::assignTrainee
* @see app/Http/Controllers/MentorOverviewController.php:614
* @route '/overview/assign-trainee'
*/
const assignTraineeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: assignTrainee.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::assignTrainee
* @see app/Http/Controllers/MentorOverviewController.php:614
* @route '/overview/assign-trainee'
*/
assignTraineeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: assignTrainee.url(options),
    method: 'post',
})

assignTrainee.form = assignTraineeForm

/**
* @see \App\Http\Controllers\MentorOverviewController::addMentor
* @see app/Http/Controllers/MentorOverviewController.php:728
* @route '/overview/add-mentor'
*/
export const addMentor = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: addMentor.url(options),
    method: 'post',
})

addMentor.definition = {
    methods: ["post"],
    url: '/overview/add-mentor',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MentorOverviewController::addMentor
* @see app/Http/Controllers/MentorOverviewController.php:728
* @route '/overview/add-mentor'
*/
addMentor.url = (options?: RouteQueryOptions) => {
    return addMentor.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MentorOverviewController::addMentor
* @see app/Http/Controllers/MentorOverviewController.php:728
* @route '/overview/add-mentor'
*/
addMentor.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: addMentor.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::addMentor
* @see app/Http/Controllers/MentorOverviewController.php:728
* @route '/overview/add-mentor'
*/
const addMentorForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: addMentor.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::addMentor
* @see app/Http/Controllers/MentorOverviewController.php:728
* @route '/overview/add-mentor'
*/
addMentorForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: addMentor.url(options),
    method: 'post',
})

addMentor.form = addMentorForm

/**
* @see \App\Http\Controllers\MentorOverviewController::removeMentor
* @see app/Http/Controllers/MentorOverviewController.php:777
* @route '/overview/remove-mentor'
*/
export const removeMentor = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: removeMentor.url(options),
    method: 'post',
})

removeMentor.definition = {
    methods: ["post"],
    url: '/overview/remove-mentor',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MentorOverviewController::removeMentor
* @see app/Http/Controllers/MentorOverviewController.php:777
* @route '/overview/remove-mentor'
*/
removeMentor.url = (options?: RouteQueryOptions) => {
    return removeMentor.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MentorOverviewController::removeMentor
* @see app/Http/Controllers/MentorOverviewController.php:777
* @route '/overview/remove-mentor'
*/
removeMentor.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: removeMentor.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::removeMentor
* @see app/Http/Controllers/MentorOverviewController.php:777
* @route '/overview/remove-mentor'
*/
const removeMentorForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: removeMentor.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::removeMentor
* @see app/Http/Controllers/MentorOverviewController.php:777
* @route '/overview/remove-mentor'
*/
removeMentorForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: removeMentor.url(options),
    method: 'post',
})

removeMentor.form = removeMentorForm

/**
* @see \App\Http\Controllers\MentorOverviewController::pastTrainees
* @see app/Http/Controllers/MentorOverviewController.php:1112
* @route '/overview/past-trainees/{course}'
*/
export const pastTrainees = (args: { course: string | number } | [course: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pastTrainees.url(args, options),
    method: 'get',
})

pastTrainees.definition = {
    methods: ["get","head"],
    url: '/overview/past-trainees/{course}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MentorOverviewController::pastTrainees
* @see app/Http/Controllers/MentorOverviewController.php:1112
* @route '/overview/past-trainees/{course}'
*/
pastTrainees.url = (args: { course: string | number } | [course: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { course: args }
    }

    if (Array.isArray(args)) {
        args = {
            course: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        course: args.course,
    }

    return pastTrainees.definition.url
            .replace('{course}', parsedArgs.course.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MentorOverviewController::pastTrainees
* @see app/Http/Controllers/MentorOverviewController.php:1112
* @route '/overview/past-trainees/{course}'
*/
pastTrainees.get = (args: { course: string | number } | [course: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: pastTrainees.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::pastTrainees
* @see app/Http/Controllers/MentorOverviewController.php:1112
* @route '/overview/past-trainees/{course}'
*/
pastTrainees.head = (args: { course: string | number } | [course: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: pastTrainees.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::pastTrainees
* @see app/Http/Controllers/MentorOverviewController.php:1112
* @route '/overview/past-trainees/{course}'
*/
const pastTraineesForm = (args: { course: string | number } | [course: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: pastTrainees.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::pastTrainees
* @see app/Http/Controllers/MentorOverviewController.php:1112
* @route '/overview/past-trainees/{course}'
*/
pastTraineesForm.get = (args: { course: string | number } | [course: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: pastTrainees.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::pastTrainees
* @see app/Http/Controllers/MentorOverviewController.php:1112
* @route '/overview/past-trainees/{course}'
*/
pastTraineesForm.head = (args: { course: string | number } | [course: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: pastTrainees.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

pastTrainees.form = pastTraineesForm

/**
* @see \App\Http\Controllers\MentorOverviewController::reactivateTrainee
* @see app/Http/Controllers/MentorOverviewController.php:1166
* @route '/overview/reactivate-trainee'
*/
export const reactivateTrainee = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reactivateTrainee.url(options),
    method: 'post',
})

reactivateTrainee.definition = {
    methods: ["post"],
    url: '/overview/reactivate-trainee',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MentorOverviewController::reactivateTrainee
* @see app/Http/Controllers/MentorOverviewController.php:1166
* @route '/overview/reactivate-trainee'
*/
reactivateTrainee.url = (options?: RouteQueryOptions) => {
    return reactivateTrainee.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MentorOverviewController::reactivateTrainee
* @see app/Http/Controllers/MentorOverviewController.php:1166
* @route '/overview/reactivate-trainee'
*/
reactivateTrainee.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reactivateTrainee.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::reactivateTrainee
* @see app/Http/Controllers/MentorOverviewController.php:1166
* @route '/overview/reactivate-trainee'
*/
const reactivateTraineeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reactivateTrainee.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::reactivateTrainee
* @see app/Http/Controllers/MentorOverviewController.php:1166
* @route '/overview/reactivate-trainee'
*/
reactivateTraineeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reactivateTrainee.url(options),
    method: 'post',
})

reactivateTrainee.form = reactivateTraineeForm

/**
* @see \App\Http\Controllers\MentorOverviewController::addTraineeToCourse
* @see app/Http/Controllers/MentorOverviewController.php:834
* @route '/overview/add-trainee-to-course'
*/
export const addTraineeToCourse = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: addTraineeToCourse.url(options),
    method: 'post',
})

addTraineeToCourse.definition = {
    methods: ["post"],
    url: '/overview/add-trainee-to-course',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MentorOverviewController::addTraineeToCourse
* @see app/Http/Controllers/MentorOverviewController.php:834
* @route '/overview/add-trainee-to-course'
*/
addTraineeToCourse.url = (options?: RouteQueryOptions) => {
    return addTraineeToCourse.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MentorOverviewController::addTraineeToCourse
* @see app/Http/Controllers/MentorOverviewController.php:834
* @route '/overview/add-trainee-to-course'
*/
addTraineeToCourse.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: addTraineeToCourse.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::addTraineeToCourse
* @see app/Http/Controllers/MentorOverviewController.php:834
* @route '/overview/add-trainee-to-course'
*/
const addTraineeToCourseForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: addTraineeToCourse.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::addTraineeToCourse
* @see app/Http/Controllers/MentorOverviewController.php:834
* @route '/overview/add-trainee-to-course'
*/
addTraineeToCourseForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: addTraineeToCourse.url(options),
    method: 'post',
})

addTraineeToCourse.form = addTraineeToCourseForm

/**
* @see \App\Http\Controllers\TraineeOrderController::updateTraineeOrder
* @see app/Http/Controllers/TraineeOrderController.php:14
* @route '/overview/update-trainee-order'
*/
export const updateTraineeOrder = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateTraineeOrder.url(options),
    method: 'post',
})

updateTraineeOrder.definition = {
    methods: ["post"],
    url: '/overview/update-trainee-order',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TraineeOrderController::updateTraineeOrder
* @see app/Http/Controllers/TraineeOrderController.php:14
* @route '/overview/update-trainee-order'
*/
updateTraineeOrder.url = (options?: RouteQueryOptions) => {
    return updateTraineeOrder.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TraineeOrderController::updateTraineeOrder
* @see app/Http/Controllers/TraineeOrderController.php:14
* @route '/overview/update-trainee-order'
*/
updateTraineeOrder.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateTraineeOrder.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TraineeOrderController::updateTraineeOrder
* @see app/Http/Controllers/TraineeOrderController.php:14
* @route '/overview/update-trainee-order'
*/
const updateTraineeOrderForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateTraineeOrder.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TraineeOrderController::updateTraineeOrder
* @see app/Http/Controllers/TraineeOrderController.php:14
* @route '/overview/update-trainee-order'
*/
updateTraineeOrderForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateTraineeOrder.url(options),
    method: 'post',
})

updateTraineeOrder.form = updateTraineeOrderForm

/**
* @see \App\Http\Controllers\TraineeOrderController::resetTraineeOrder
* @see app/Http/Controllers/TraineeOrderController.php:74
* @route '/overview/reset-trainee-order'
*/
export const resetTraineeOrder = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resetTraineeOrder.url(options),
    method: 'post',
})

resetTraineeOrder.definition = {
    methods: ["post"],
    url: '/overview/reset-trainee-order',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TraineeOrderController::resetTraineeOrder
* @see app/Http/Controllers/TraineeOrderController.php:74
* @route '/overview/reset-trainee-order'
*/
resetTraineeOrder.url = (options?: RouteQueryOptions) => {
    return resetTraineeOrder.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TraineeOrderController::resetTraineeOrder
* @see app/Http/Controllers/TraineeOrderController.php:74
* @route '/overview/reset-trainee-order'
*/
resetTraineeOrder.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: resetTraineeOrder.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TraineeOrderController::resetTraineeOrder
* @see app/Http/Controllers/TraineeOrderController.php:74
* @route '/overview/reset-trainee-order'
*/
const resetTraineeOrderForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resetTraineeOrder.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TraineeOrderController::resetTraineeOrder
* @see app/Http/Controllers/TraineeOrderController.php:74
* @route '/overview/reset-trainee-order'
*/
resetTraineeOrderForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: resetTraineeOrder.url(options),
    method: 'post',
})

resetTraineeOrder.form = resetTraineeOrderForm

/**
* @see \App\Http\Controllers\MentorOverviewController::grantEndorsement
* @see app/Http/Controllers/MentorOverviewController.php:945
* @route '/overview/grant-endorsement'
*/
export const grantEndorsement = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: grantEndorsement.url(options),
    method: 'post',
})

grantEndorsement.definition = {
    methods: ["post"],
    url: '/overview/grant-endorsement',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MentorOverviewController::grantEndorsement
* @see app/Http/Controllers/MentorOverviewController.php:945
* @route '/overview/grant-endorsement'
*/
grantEndorsement.url = (options?: RouteQueryOptions) => {
    return grantEndorsement.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MentorOverviewController::grantEndorsement
* @see app/Http/Controllers/MentorOverviewController.php:945
* @route '/overview/grant-endorsement'
*/
grantEndorsement.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: grantEndorsement.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::grantEndorsement
* @see app/Http/Controllers/MentorOverviewController.php:945
* @route '/overview/grant-endorsement'
*/
const grantEndorsementForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: grantEndorsement.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::grantEndorsement
* @see app/Http/Controllers/MentorOverviewController.php:945
* @route '/overview/grant-endorsement'
*/
grantEndorsementForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: grantEndorsement.url(options),
    method: 'post',
})

grantEndorsement.form = grantEndorsementForm

/**
* @see \App\Http\Controllers\MentorOverviewController::getMoodleStatusTrainee
* @see app/Http/Controllers/MentorOverviewController.php:239
* @route '/overview/moodle-status-trainee'
*/
export const getMoodleStatusTrainee = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: getMoodleStatusTrainee.url(options),
    method: 'post',
})

getMoodleStatusTrainee.definition = {
    methods: ["post"],
    url: '/overview/moodle-status-trainee',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MentorOverviewController::getMoodleStatusTrainee
* @see app/Http/Controllers/MentorOverviewController.php:239
* @route '/overview/moodle-status-trainee'
*/
getMoodleStatusTrainee.url = (options?: RouteQueryOptions) => {
    return getMoodleStatusTrainee.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MentorOverviewController::getMoodleStatusTrainee
* @see app/Http/Controllers/MentorOverviewController.php:239
* @route '/overview/moodle-status-trainee'
*/
getMoodleStatusTrainee.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: getMoodleStatusTrainee.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::getMoodleStatusTrainee
* @see app/Http/Controllers/MentorOverviewController.php:239
* @route '/overview/moodle-status-trainee'
*/
const getMoodleStatusTraineeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: getMoodleStatusTrainee.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::getMoodleStatusTrainee
* @see app/Http/Controllers/MentorOverviewController.php:239
* @route '/overview/moodle-status-trainee'
*/
getMoodleStatusTraineeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: getMoodleStatusTrainee.url(options),
    method: 'post',
})

getMoodleStatusTrainee.form = getMoodleStatusTraineeForm

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

const overview = {
    getCourseMentors: Object.assign(getCourseMentors, getCourseMentors),
    index: Object.assign(index, index),
    course: Object.assign(course, course),
    updateRemark: Object.assign(updateRemark, updateRemark),
    removeTrainee: Object.assign(removeTrainee, removeTrainee),
    finishTrainee: Object.assign(finishTrainee, finishTrainee),
    claimTrainee: Object.assign(claimTrainee, claimTrainee),
    unclaimTrainee: Object.assign(unclaimTrainee, unclaimTrainee),
    assignTrainee: Object.assign(assignTrainee, assignTrainee),
    addMentor: Object.assign(addMentor, addMentor),
    removeMentor: Object.assign(removeMentor, removeMentor),
    pastTrainees: Object.assign(pastTrainees, pastTrainees),
    reactivateTrainee: Object.assign(reactivateTrainee, reactivateTrainee),
    addTraineeToCourse: Object.assign(addTraineeToCourse, addTraineeToCourse),
    updateTraineeOrder: Object.assign(updateTraineeOrder, updateTraineeOrder),
    resetTraineeOrder: Object.assign(resetTraineeOrder, resetTraineeOrder),
    grantEndorsement: Object.assign(grantEndorsement, grantEndorsement),
    getMoodleStatusTrainee: Object.assign(getMoodleStatusTrainee, getMoodleStatusTrainee),
    addSolo: Object.assign(addSolo, addSolo),
    extendSolo: Object.assign(extendSolo, extendSolo),
    removeSolo: Object.assign(removeSolo, removeSolo),
    getSoloRequirements: Object.assign(getSoloRequirements, getSoloRequirements),
    assignCoreTest: Object.assign(assignCoreTest, assignCoreTest),
}

export default overview