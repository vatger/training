import ListTrainingLogs from './ListTrainingLogs'
import CreateTrainingLog from './CreateTrainingLog'
import ViewTrainingLog from './ViewTrainingLog'
import EditTrainingLog from './EditTrainingLog'

const Pages = {
    ListTrainingLogs: Object.assign(ListTrainingLogs, ListTrainingLogs),
    CreateTrainingLog: Object.assign(CreateTrainingLog, CreateTrainingLog),
    ViewTrainingLog: Object.assign(ViewTrainingLog, ViewTrainingLog),
    EditTrainingLog: Object.assign(EditTrainingLog, EditTrainingLog),
}

export default Pages