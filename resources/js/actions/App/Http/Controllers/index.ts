import Auth from './Auth'
import DashboardController from './DashboardController'
import EndorsementController from './EndorsementController'
import CourseController from './CourseController'
import WaitingListController from './WaitingListController'
import FamiliarisationController from './FamiliarisationController'
import UserSearchController from './UserSearchController'
import MentorOverviewController from './MentorOverviewController'
import TraineeOrderController from './TraineeOrderController'
import SoloController from './SoloController'
import TrainingLogController from './TrainingLogController'
import CptController from './CptController'
import Settings from './Settings'

const Controllers = {
    Auth: Object.assign(Auth, Auth),
    DashboardController: Object.assign(DashboardController, DashboardController),
    EndorsementController: Object.assign(EndorsementController, EndorsementController),
    CourseController: Object.assign(CourseController, CourseController),
    WaitingListController: Object.assign(WaitingListController, WaitingListController),
    FamiliarisationController: Object.assign(FamiliarisationController, FamiliarisationController),
    UserSearchController: Object.assign(UserSearchController, UserSearchController),
    MentorOverviewController: Object.assign(MentorOverviewController, MentorOverviewController),
    TraineeOrderController: Object.assign(TraineeOrderController, TraineeOrderController),
    SoloController: Object.assign(SoloController, SoloController),
    TrainingLogController: Object.assign(TrainingLogController, TrainingLogController),
    CptController: Object.assign(CptController, CptController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers