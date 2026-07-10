<?php

use App\Http\Controllers\Training\MentorOverviewController;
use App\Http\Controllers\Training\MentorManagementController;
use App\Http\Controllers\Training\TraineeManagementController;
use App\Http\Controllers\Training\TrainingRemarkController;
use App\Http\Controllers\Training\WaitingListController;
use App\Http\Controllers\Endorsement\EndorsementController;
use App\Http\Controllers\FamiliarisationController;
use App\Http\Controllers\UserSearchController;
use App\Http\Controllers\TraineeOrderController;
use App\Http\Controllers\Solo\SoloController;
use App\Http\Controllers\TrainingLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Cpt\CptController;
use App\Http\Controllers\UserSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect("/dashboard");
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [UserSettingsController::class, 'index'])->name('index');
        Route::post('/', [UserSettingsController::class, 'update'])->name('update');
    });

    Route::prefix('endorsements')->name('endorsements.')->group(function () {
        Route::get('/my-endorsements', [EndorsementController::class, 'traineeView'])
            ->name('trainee');

        Route::middleware('mentor')->group(function () {
            Route::get('/manage', [EndorsementController::class, 'mentorView'])
                ->name('manage');

            Route::delete('/tier1/{endorsementId}/remove', [EndorsementController::class, 'removeTier1'])
                ->name('tier1.remove');
        });

        Route::post('/tier2/{tier2Id}/request', [EndorsementController::class, 'requestTier2'])
            ->name('tier2.request');
    });

    Route::prefix('courses')->name('courses.')->group(function () {
        Route::get('/', [MentorManagementController::class, 'index'])->name('index');
        Route::post('/{course}/waiting-list', [MentorManagementController::class, 'toggleWaitingList'])->name('toggle-waiting-list');
    });

    Route::prefix('waiting-lists')->name('waiting-lists.')->middleware('mentor')->group(function () {
        Route::get('/manage', [WaitingListController::class, 'mentorView'])->name('manage');
        Route::post('/{entry}/start-training', [WaitingListController::class, 'startTraining'])->name('start-training');
        Route::post('/update-remarks', [WaitingListController::class, 'updateRemarks'])->name('update-remarks');
    });

    Route::prefix('familiarisations')->name('familiarisations.')->group(function () {
        Route::get('/', [FamiliarisationController::class, 'index'])->name('index');
        Route::get('/my-familiarisations', [FamiliarisationController::class, 'userFamiliarisations'])->name('user');
    });

    Route::middleware('mentor')->group(function () {
        Route::post('users/search', [UserSearchController::class, 'search'])->name('users.search');
        Route::get('users/{vatsimId}', [UserSearchController::class, 'show'])->name('users.profile');

        Route::prefix('overview')->name('overview.')->group(function () {
            Route::get('/', [MentorOverviewController::class, 'index'])
                ->name('index');

            Route::get('/mentor-overview/course/{courseId}/trainees', [MentorOverviewController::class, 'loadCourseTrainees'])
                ->name('course.trainees');

            Route::get('/trainee-logs/{traineeId}', [MentorOverviewController::class, 'getTraineeLogs'])
                ->name('trainee-logs');

            Route::get('/course/{course}/mentors', [MentorOverviewController::class, 'getCourseMentors'])
                ->name('course.mentors');

            Route::post('/update-remark', [TrainingRemarkController::class, 'updateRemark'])
                ->name('update-remark');

            Route::post('/remove-trainee', [TraineeManagementController::class, 'removeTrainee'])
                ->name('remove-trainee');

            Route::post('/finish-trainee', [TraineeManagementController::class, 'finishCourse'])
                ->name('finish-trainee');

            Route::post('/claim-trainee', [TraineeManagementController::class, 'claimTrainee'])
                ->name('claim-trainee');
            Route::post('/unclaim-trainee', [TraineeManagementController::class, 'unclaimTrainee'])
                ->name('unclaim-trainee');
            Route::post('/assign-trainee', [TraineeManagementController::class, 'assignTrainee'])
                ->name('assign-trainee');

            Route::post('/add-mentor', [MentorManagementController::class, 'addMentor'])
                ->name('add-mentor');
            Route::post('/remove-mentor', [MentorManagementController::class, 'removeMentor'])
                ->name('remove-mentor');

            Route::get('/past-trainees/{course}', [MentorOverviewController::class, 'getPastTrainees'])
                ->name('past-trainees');

            Route::post('/reactivate-trainee', [TraineeManagementController::class, 'reactivateTrainee'])
                ->name('reactivate-trainee');

            Route::post('/add-trainee-to-course', [TraineeManagementController::class, 'addTraineeToCourse'])
                ->name('add-trainee-to-course');

            Route::post('/update-trainee-order', [TraineeOrderController::class, 'updateOrder'])
                ->name('update-trainee-order');
            Route::post('/reset-trainee-order', [TraineeOrderController::class, 'resetOrder'])
                ->name('reset-trainee-order');

            Route::post('/grant-endorsement', [MentorOverviewController::class, 'grantEndorsement'])
                ->name('grant-endorsement');

            Route::post('/moodle-status-batch', [MentorOverviewController::class, 'getMoodleStatusBatch'])
                ->name('get-moodle-status-batch');

            Route::post('/solo/add', [SoloController::class, 'addSolo'])->name('add-solo');
            Route::post('/solo/extend', [SoloController::class, 'extendSolo'])->name('extend-solo');
            Route::post('/solo/remove', [SoloController::class, 'removeSolo'])->name('remove-solo');
            Route::post('/solo/requirements', [SoloController::class, 'getSoloRequirements'])
                ->name('get-solo-requirements');
            Route::post('/solo/assign-test', [SoloController::class, 'assignCoreTest'])
                ->name('assign-core-test');
        });

        Route::prefix('cpt')->name('cpt.')->group(function () {
            Route::get('/', [CptController::class, 'index'])->name('index');
            Route::get('/create', [CptController::class, 'create'])->name('create');
            Route::post('/', [CptController::class, 'store'])->name('store');
            Route::get('/course-data', [CptController::class, 'getCourseData'])->name('course-data');

            Route::get('/log/{log}', [CptController::class, 'viewLog'])->name('log.view');

            Route::post('/{cpt}/join-examiner', [CptController::class, 'joinExaminer'])->name('join-examiner');
            Route::post('/{cpt}/leave-examiner', [CptController::class, 'leaveExaminer'])->name('leave-examiner');
            Route::post('/{cpt}/join-local', [CptController::class, 'joinLocal'])->name('join-local');
            Route::post('/{cpt}/leave-local', [CptController::class, 'leaveLocal'])->name('leave-local');

            Route::get('/{cpt}/upload', [CptController::class, 'uploadPage'])->name('upload');
            Route::post('/{cpt}/upload', [CptController::class, 'upload'])->name('upload.store');

            Route::delete('/{cpt}', [CptController::class, 'destroy'])->name('destroy');

            Route::post('/{cpt}/grade/{result}', [CptController::class, 'grade'])->name('grade');
        });
    });

    Route::prefix('training-logs')->name('training-logs.')->group(function () {
        Route::get('/{trainingLog}', [TrainingLogController::class, 'show'])->name('show');

        // Mentor-only: create, edit, mutate
        Route::middleware('mentor')->group(function () {
            Route::get('/', [TrainingLogController::class, 'index'])->name('index');
            Route::get('/create/{traineeId}/{courseId}', [TrainingLogController::class, 'create'])->name('create');
            Route::post('/', [TrainingLogController::class, 'store'])->name('store');
            Route::get('/{trainingLog}/edit', [TrainingLogController::class, 'edit'])->name('edit');
            Route::put('/{trainingLog}', [TrainingLogController::class, 'update'])->name('update');
            Route::delete('/{trainingLog}', [TrainingLogController::class, 'destroy'])->name('destroy');
        });
    });
});

require __DIR__ . '/auth.php';