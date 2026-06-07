<?php

namespace App\Providers;

use App\Domain\Training\Events\CourseFinished;
use App\Domain\Training\Events\MentorAdded;
use App\Domain\Training\Events\MentorRemoved;
use App\Domain\Training\Events\TraineeAddedToCourse;
use App\Domain\Training\Events\TraineeAssigned;
use App\Domain\Training\Events\TraineeClaimed;
use App\Domain\Training\Events\TraineeReactivated;
use App\Domain\Training\Events\TraineeRemarkUpdated;
use App\Domain\Training\Events\TraineeRemoved;
use App\Domain\Training\Events\TraineeUnclaimed;
use App\Domain\Training\Events\TrainingStarted;
use App\Domain\WaitingList\Events\WaitingListJoined;
use App\Domain\WaitingList\Events\WaitingListLeft;
use App\Listeners\LogCourseFinished;
use App\Listeners\LogMentorAdded;
use App\Listeners\LogMentorRemoved;
use App\Listeners\LogTraineeAddedToCourse;
use App\Listeners\LogTraineeAssigned;
use App\Listeners\LogTraineeClaimed;
use App\Listeners\LogTraineeReactivated;
use App\Listeners\LogTraineeRemarkUpdated;
use App\Listeners\LogTraineeRemoved;
use App\Listeners\LogTraineeUnclaimed;
use App\Listeners\LogTrainingStarted;
use App\Listeners\LogWaitingListJoined;
use App\Listeners\LogWaitingListLeft;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        TrainingStarted::class => [LogTrainingStarted::class],
        CourseFinished::class => [LogCourseFinished::class],
        TraineeClaimed::class => [LogTraineeClaimed::class],
        TraineeUnclaimed::class => [LogTraineeUnclaimed::class],
        TraineeAssigned::class => [LogTraineeAssigned::class],
        TraineeRemoved::class => [LogTraineeRemoved::class],
        TraineeReactivated::class => [LogTraineeReactivated::class],
        TraineeAddedToCourse::class => [LogTraineeAddedToCourse::class],
        MentorAdded::class => [LogMentorAdded::class],
        MentorRemoved::class => [LogMentorRemoved::class],
        TraineeRemarkUpdated::class => [LogTraineeRemarkUpdated::class],
        WaitingListJoined::class => [LogWaitingListJoined::class],
        WaitingListLeft::class => [LogWaitingListLeft::class],
        SoloGranted::class => [LogSoloGranted::class],
        SoloExtended::class => [LogSoloExtended::class],
        SoloRemoved::class => [LogSoloRemoved::class],
    ];
}