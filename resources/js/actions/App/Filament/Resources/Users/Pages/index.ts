import ListUsers from './ListUsers'
import EditUser from './EditUser'

const Pages = {
    ListUsers: Object.assign(ListUsers, ListUsers),
    EditUser: Object.assign(EditUser, EditUser),
}

export default Pages