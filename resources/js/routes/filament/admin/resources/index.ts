import activityLogs from './activity-logs'
import courses from './courses'
import cpts from './cpts'
import endorsementActivities from './endorsement-activities'
import examiners from './examiners'
import familiarisationSectors from './familiarisation-sectors'
import familiarisations from './familiarisations'
import roles from './roles'
import tier2Endorsements from './tier2-endorsements'
import trainingLogs from './training-logs'
import users from './users'
import waitingLists from './waiting-lists'

const resources = {
    activityLogs: Object.assign(activityLogs, activityLogs),
    courses: Object.assign(courses, courses),
    cpts: Object.assign(cpts, cpts),
    endorsementActivities: Object.assign(endorsementActivities, endorsementActivities),
    examiners: Object.assign(examiners, examiners),
    familiarisationSectors: Object.assign(familiarisationSectors, familiarisationSectors),
    familiarisations: Object.assign(familiarisations, familiarisations),
    roles: Object.assign(roles, roles),
    tier2Endorsements: Object.assign(tier2Endorsements, tier2Endorsements),
    trainingLogs: Object.assign(trainingLogs, trainingLogs),
    users: Object.assign(users, users),
    waitingLists: Object.assign(waitingLists, waitingLists),
}

export default resources