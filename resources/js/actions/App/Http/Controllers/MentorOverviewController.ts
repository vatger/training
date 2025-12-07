import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
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
* @see \App\Http\Controllers\MentorOverviewController::loadCourseTrainees
* @see app/Http/Controllers/MentorOverviewController.php:122
* @route '/overview/mentor-overview/course/{courseId}/trainees'
*/
export const loadCourseTrainees = (args: { courseId: string | number } | [courseId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: loadCourseTrainees.url(args, options),
    method: 'get',
})

loadCourseTrainees.definition = {
    methods: ["get","head"],
    url: '/overview/mentor-overview/course/{courseId}/trainees',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MentorOverviewController::loadCourseTrainees
* @see app/Http/Controllers/MentorOverviewController.php:122
* @route '/overview/mentor-overview/course/{courseId}/trainees'
*/
loadCourseTrainees.url = (args: { courseId: string | number } | [courseId: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { courseId: args }
    }

    if (Array.isArray(args)) {
        args = {
            courseId: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        courseId: args.courseId,
    }

    return loadCourseTrainees.definition.url
            .replace('{courseId}', parsedArgs.courseId.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MentorOverviewController::loadCourseTrainees
* @see app/Http/Controllers/MentorOverviewController.php:122
* @route '/overview/mentor-overview/course/{courseId}/trainees'
*/
loadCourseTrainees.get = (args: { courseId: string | number } | [courseId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: loadCourseTrainees.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::loadCourseTrainees
* @see app/Http/Controllers/MentorOverviewController.php:122
* @route '/overview/mentor-overview/course/{courseId}/trainees'
*/
loadCourseTrainees.head = (args: { courseId: string | number } | [courseId: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: loadCourseTrainees.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::loadCourseTrainees
* @see app/Http/Controllers/MentorOverviewController.php:122
* @route '/overview/mentor-overview/course/{courseId}/trainees'
*/
const loadCourseTraineesForm = (args: { courseId: string | number } | [courseId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: loadCourseTrainees.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::loadCourseTrainees
* @see app/Http/Controllers/MentorOverviewController.php:122
* @route '/overview/mentor-overview/course/{courseId}/trainees'
*/
loadCourseTraineesForm.get = (args: { courseId: string | number } | [courseId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: loadCourseTrainees.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::loadCourseTrainees
* @see app/Http/Controllers/MentorOverviewController.php:122
* @route '/overview/mentor-overview/course/{courseId}/trainees'
*/
loadCourseTraineesForm.head = (args: { courseId: string | number } | [courseId: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: loadCourseTrainees.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

loadCourseTrainees.form = loadCourseTraineesForm

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
* @see \App\Http\Controllers\MentorOverviewController::finishCourse
* @see app/Http/Controllers/MentorOverviewController.php:1044
* @route '/overview/finish-trainee'
*/
export const finishCourse = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: finishCourse.url(options),
    method: 'post',
})

finishCourse.definition = {
    methods: ["post"],
    url: '/overview/finish-trainee',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MentorOverviewController::finishCourse
* @see app/Http/Controllers/MentorOverviewController.php:1044
* @route '/overview/finish-trainee'
*/
finishCourse.url = (options?: RouteQueryOptions) => {
    return finishCourse.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MentorOverviewController::finishCourse
* @see app/Http/Controllers/MentorOverviewController.php:1044
* @route '/overview/finish-trainee'
*/
finishCourse.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: finishCourse.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::finishCourse
* @see app/Http/Controllers/MentorOverviewController.php:1044
* @route '/overview/finish-trainee'
*/
const finishCourseForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: finishCourse.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::finishCourse
* @see app/Http/Controllers/MentorOverviewController.php:1044
* @route '/overview/finish-trainee'
*/
finishCourseForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: finishCourse.url(options),
    method: 'post',
})

finishCourse.form = finishCourseForm

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
* @see \App\Http\Controllers\MentorOverviewController::getPastTrainees
* @see app/Http/Controllers/MentorOverviewController.php:1112
* @route '/overview/past-trainees/{course}'
*/
export const getPastTrainees = (args: { course: string | number } | [course: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getPastTrainees.url(args, options),
    method: 'get',
})

getPastTrainees.definition = {
    methods: ["get","head"],
    url: '/overview/past-trainees/{course}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MentorOverviewController::getPastTrainees
* @see app/Http/Controllers/MentorOverviewController.php:1112
* @route '/overview/past-trainees/{course}'
*/
getPastTrainees.url = (args: { course: string | number } | [course: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return getPastTrainees.definition.url
            .replace('{course}', parsedArgs.course.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MentorOverviewController::getPastTrainees
* @see app/Http/Controllers/MentorOverviewController.php:1112
* @route '/overview/past-trainees/{course}'
*/
getPastTrainees.get = (args: { course: string | number } | [course: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getPastTrainees.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::getPastTrainees
* @see app/Http/Controllers/MentorOverviewController.php:1112
* @route '/overview/past-trainees/{course}'
*/
getPastTrainees.head = (args: { course: string | number } | [course: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getPastTrainees.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::getPastTrainees
* @see app/Http/Controllers/MentorOverviewController.php:1112
* @route '/overview/past-trainees/{course}'
*/
const getPastTraineesForm = (args: { course: string | number } | [course: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getPastTrainees.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::getPastTrainees
* @see app/Http/Controllers/MentorOverviewController.php:1112
* @route '/overview/past-trainees/{course}'
*/
getPastTraineesForm.get = (args: { course: string | number } | [course: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getPastTrainees.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::getPastTrainees
* @see app/Http/Controllers/MentorOverviewController.php:1112
* @route '/overview/past-trainees/{course}'
*/
getPastTraineesForm.head = (args: { course: string | number } | [course: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: getPastTrainees.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

getPastTrainees.form = getPastTraineesForm

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
* @see \App\Http\Controllers\MentorOverviewController::getMoodleStatusForTrainee
* @see app/Http/Controllers/MentorOverviewController.php:239
* @route '/overview/moodle-status-trainee'
*/
export const getMoodleStatusForTrainee = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: getMoodleStatusForTrainee.url(options),
    method: 'post',
})

getMoodleStatusForTrainee.definition = {
    methods: ["post"],
    url: '/overview/moodle-status-trainee',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\MentorOverviewController::getMoodleStatusForTrainee
* @see app/Http/Controllers/MentorOverviewController.php:239
* @route '/overview/moodle-status-trainee'
*/
getMoodleStatusForTrainee.url = (options?: RouteQueryOptions) => {
    return getMoodleStatusForTrainee.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MentorOverviewController::getMoodleStatusForTrainee
* @see app/Http/Controllers/MentorOverviewController.php:239
* @route '/overview/moodle-status-trainee'
*/
getMoodleStatusForTrainee.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: getMoodleStatusForTrainee.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::getMoodleStatusForTrainee
* @see app/Http/Controllers/MentorOverviewController.php:239
* @route '/overview/moodle-status-trainee'
*/
const getMoodleStatusForTraineeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: getMoodleStatusForTrainee.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\MentorOverviewController::getMoodleStatusForTrainee
* @see app/Http/Controllers/MentorOverviewController.php:239
* @route '/overview/moodle-status-trainee'
*/
getMoodleStatusForTraineeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: getMoodleStatusForTrainee.url(options),
    method: 'post',
})

getMoodleStatusForTrainee.form = getMoodleStatusForTraineeForm

const MentorOverviewController = { getCourseMentors, index, loadCourseTrainees, updateRemark, removeTrainee, finishCourse, claimTrainee, unclaimTrainee, assignTrainee, addMentor, removeMentor, getPastTrainees, reactivateTrainee, addTraineeToCourse, grantEndorsement, getMoodleStatusForTrainee }

export default MentorOverviewController