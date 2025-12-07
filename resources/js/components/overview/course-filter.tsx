import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { getPositionColor, getPositionIcon } from '@/lib/course-utils';
import { MentorCourse } from '@/types/mentor';

interface CourseFilterProps {
    courses: MentorCourse[];
    activeCategory: string;
    selectedCourse: MentorCourse | null;
    onCategoryChange: (category: string) => void;
    onCourseSelect: (course: MentorCourse) => void;
}

export function CourseFilter({
    courses,
    activeCategory,
    selectedCourse,
    onCategoryChange,
    onCourseSelect,
}: CourseFilterProps) {
    const getCategoryCount = (category: string) => {
        if (category === 'EDMT_FAM') {
            return courses.filter((c) => c.type === 'EDMT' || c.type === 'FAM').length;
        }
        return courses.filter((c) => c.type === category).length;
    };

    const filteredCourses = courses.filter((course) => {
        if (activeCategory === 'EDMT_FAM') {
            return course.type === 'EDMT' || course.type === 'FAM';
        }
        return course.type === activeCategory;
    });

    return (
        <Card className="gap-4 py-0 pb-4">
            <CardHeader className="!gap-0 border-b !p-0">
                <Tabs value={activeCategory} onValueChange={onCategoryChange}>
                    <TabsList className="w-full justify-start rounded-bl-none">
                        {getCategoryCount('RTG') > 0 && (
                            <TabsTrigger className="max-w-68" value="RTG">
                                Ratings ({getCategoryCount('RTG')})
                            </TabsTrigger>
                        )}
                        {getCategoryCount('EDMT_FAM') > 0 && (
                            <TabsTrigger className="max-w-68" value="EDMT_FAM">
                                Endorsements & Familiarisation ({getCategoryCount('EDMT_FAM')})
                            </TabsTrigger>
                        )}
                        {getCategoryCount('GST') > 0 && (
                            <TabsTrigger className="max-w-68" value="GST">
                                Visitor ({getCategoryCount('GST')})
                            </TabsTrigger>
                        )}
                    </TabsList>
                </Tabs>
            </CardHeader>

            <CardContent className="px-4">
                {filteredCourses.length > 0 ? (
                    <div className="flex flex-wrap gap-2">
                        {filteredCourses.map((course) => (
                            <button
                                key={course.id}
                                onClick={() => onCourseSelect(course)}
                                className={`inline-flex items-center gap-2 rounded-full border px-1 py-1 text-sm font-medium transition-colors ${
                                    selectedCourse?.id === course.id
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'border-border bg-background hover:bg-muted'
                                }`}
                            >
                                <div className={`rounded-full p-1 ${getPositionColor(course.position)}`}>
                                    {getPositionIcon(course.position)}
                                </div>
                                {course.name}
                                <Badge variant="secondary" className="ml-1 rounded-full">
                                    {course.activeTrainees}
                                </Badge>
                            </button>
                        ))}
                    </div>
                ) : (
                    <div className="py-8 text-center text-muted-foreground">No courses available in this category</div>
                )}
            </CardContent>
        </Card>
    );
}