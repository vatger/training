import ListCourses from './ListCourses'
import CreateCourse from './CreateCourse'
import EditCourse from './EditCourse'

const Pages = {
    ListCourses: Object.assign(ListCourses, ListCourses),
    CreateCourse: Object.assign(CreateCourse, CreateCourse),
    EditCourse: Object.assign(EditCourse, EditCourse),
}

export default Pages