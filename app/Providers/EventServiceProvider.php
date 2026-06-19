<?php

namespace App\Providers;

use App\Domain\Cpt\Events\CptCreated;
use App\Domain\Cpt\Events\CptDeleted;
use App\Domain\Cpt\Events\CptExaminerJoined;
use App\Domain\Cpt\Events\CptExaminerLeft;
use App\Domain\Cpt\Events\CptGraded;
use App\Domain\Cpt\Events\CptLocalJoined;
use App\Domain\Cpt\Events\CptLocalLeft;
use App\Domain\Cpt\Events\CptLogUploaded;
use App\Domain\Endorsement\Events\EndorsementMarkedForRemoval;
use App\Domain\Endorsement\Events\Tier2EndorsementGranted;
use App\Domain\Roster\Events\RosterRemovalWarningIssued;
use App\Domain\Roster\Events\UserRemovedFromRoster;
use App\Domain\Solo\Events\SoloExtended;
use App\Domain\Solo\Events\SoloGranted;
use App\Domain\Solo\Events\SoloRemoved;
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
use App\Listeners\LogCptCreated;
use App\Listeners\LogCptDeleted;
use App\Listeners\LogCptExaminerJoined;
use App\Listeners\LogCptExaminerLeft;
use App\Listeners\LogCptGraded;
use App\Listeners\LogCptLocalJoined;
use App\Listeners\LogCptLocalLeft;
use App\Listeners\LogCptLogUploaded;
use App\Listeners\LogEndorsementMarkedForRemoval;
use App\Listeners\LogMentorAdded;
use App\Listeners\LogMentorRemoved;
use App\Listeners\LogRosterRemovalWarningIssued;
use App\Listeners\LogSoloExtended;
use App\Listeners\LogSoloGranted;
use App\Listeners\LogSoloRemoved;
use App\Listeners\LogTier2EndorsementGranted;
use App\Listeners\LogTraineeAddedToCourse;
use App\Listeners\LogTraineeAssigned;
use App\Listeners\LogTraineeClaimed;
use App\Listeners\LogTraineeReactivated;
use App\Listeners\LogTraineeRemarkUpdated;
use App\Listeners\LogTraineeRemoved;
use App\Listeners\LogTraineeUnclaimed;
use App\Listeners\LogTrainingStarted;
use App\Listeners\LogUserRemovedFromRoster;
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
        CptCreated::class => [LogCptCreated::class],
        CptGraded::class => [LogCptGraded::class],
        CptDeleted::class => [LogCptDeleted::class],
        CptExaminerJoined::class => [LogCptExaminerJoined::class],
        CptExaminerLeft::class => [LogCptExaminerLeft::class],
        CptLocalJoined::class => [LogCptLocalJoined::class],
        CptLocalLeft::class => [LogCptLocalLeft::class],
        CptLogUploaded::class => [LogCptLogUploaded::class],
        EndorsementMarkedForRemoval::class => [LogEndorsementMarkedForRemoval::class],
        Tier2EndorsementGranted::class => [LogTier2EndorsementGranted::class],
        RosterRemovalWarningIssued::class => [LogRosterRemovalWarningIssued::class],
        UserRemovedFromRoster::class => [LogUserRemovedFromRoster::class],
    ];
}