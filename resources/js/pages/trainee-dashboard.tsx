import { CourseLogsModal } from '@/components/dashboard/course-logs-modal';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { BookOpen, Calendar, CheckCircle, ExternalLink, Eye, GraduationCap, Map, MapPin } from 'lucide-react';
import { useState } from 'react';

interface TrainingLog {
    id: number;
    session_date: string;
    position: string;
    type: string;
    type_display: string;
    result: boolean;
    average_rating: number;
    mentor_name: string;
    next_step: string;
    session_duration: number;
}

export interface Course {
    id: number;
    name: string;
    trainee_display_name: string;
    type: string;
    type_display?: string;
    position: string;
    position_display: string;
    airport_icao: string;
    claimed_by: string | null;
    completed_at?: string;
    recent_logs: TrainingLog[];
    all_logs?: TrainingLog[];
}

interface MoodleCourse {
    id: number;
    name: string;
    passed: boolean;
    link: string;
}

interface Familiarisation {
    id: number;
    sector_name: string;
    fir: string;
}

interface Statistics {
    active_courses: number;
    total_sessions: number;
    completed_courses: number;
}

interface Props {
    statistics?: Statistics;
    activeCourses?: Course[];
    completedCourses?: Course[];
    moodleCourses?: MoodleCourse[];
    familiarisations?: Record<string, Familiarisation[]>;
}

const getTypeColor = (type: string) => {
    switch (type) {
        case 'RTG':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
        case 'EDMT':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300';
        case 'FAM':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
        case 'GST':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'RST':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
    }
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
];

export default function TraineeDashboard(props: Props) {
    // Destructure with defaults
    const {
        statistics = { active_courses: 0, total_sessions: 0, completed_courses: 0 },
        activeCourses = [],
        completedCourses = [],
        moodleCourses = [],
        familiarisations = {},
    } = props;

    const [selectedCourse, setSelectedCourse] = useState<Course | null>(null);
    const [logsModalOpen, setLogsModalOpen] = useState(false);

    const handleViewLogs = (course: Course) => {
        setSelectedCourse(course);
        setLogsModalOpen(true);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                {/* Statistics Cards */}
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <Card className="@container/card">
                        <CardHeader>
                            <CardDescription>Active Courses</CardDescription>
                            <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                                {statistics?.active_courses ?? 0}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-sm text-muted-foreground">Currently enrolled in training</CardContent>
                    </Card>

                    <Card className="@container/card">
                        <CardHeader>
                            <CardDescription>Training Sessions</CardDescription>
                            <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                                {statistics?.total_sessions ?? 0}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-sm text-muted-foreground">Total sessions completed</CardContent>
                    </Card>

                    <Card className="@container/card">
                        <CardHeader>
                            <CardDescription>Completed Courses</CardDescription>
                            <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                                {statistics?.completed_courses ?? 0}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-sm text-muted-foreground">Successfully finished training</CardContent>
                    </Card>
                </div>

                {/* Active Courses */}
                {activeCourses && activeCourses.length > 0 && (
                    <Accordion type="single" collapsible defaultValue="active-courses">
                        <AccordionItem value="active-courses">
                            <Card className="py-0">
                                <CardHeader className="gap-0 px-0 py-2">
                                    <AccordionTrigger className="w-full p-4 hover:no-underline [&[data-state=open]>div>svg]:rotate-180">
                                        <div className="flex items-center gap-2">
                                            <div className="rounded-full bg-primary/10 p-2">
                                                <BookOpen className="h-5 w-5 text-primary" />
                                            </div>
                                            <div className="text-left">
                                                <CardTitle>Active Courses</CardTitle>
                                                <CardDescription>Your current training courses</CardDescription>
                                            </div>
                                        </div>
                                    </AccordionTrigger>
                                </CardHeader>

                                <AccordionContent>
                                    <CardContent>
                                        <div className="grid gap-4 md:grid-cols-2">
                                            {activeCourses.map((course) => (
                                                <Card key={course.id} className="transition-shadow hover:shadow-md">
                                                    <CardHeader className="pb-3">
                                                        <div className="flex items-start justify-between gap-3">
                                                            <div className="flex-1">
                                                                <CardTitle className="text-lg">{course.trainee_display_name}</CardTitle>
                                                                <div className="mt-1 flex items-center gap-2 text-sm text-muted-foreground">
                                                                    <MapPin className="h-3 w-3" />
                                                                    {course.airport_icao}
                                                                </div>
                                                            </div>
                                                            <div className="flex flex-col gap-1">
                                                                <Badge variant="outline" className={getTypeColor(course.type)}>
                                                                    {course.type_display}
                                                                </Badge>
                                                                <Badge variant="secondary" className="justify-center">
                                                                    {course.position_display}
                                                                </Badge>
                                                            </div>
                                                        </div>
                                                    </CardHeader>
                                                    <CardContent className="space-y-3">
                                                        {course.claimed_by && (
                                                            <div className="text-sm">
                                                                <span className="text-muted-foreground">Mentor: </span>
                                                                <span className="font-medium">{course.claimed_by}</span>
                                                            </div>
                                                        )}
                                                        {course.recent_logs && course.recent_logs.length > 0 && (
                                                            <div className="space-y-2">
                                                                <div className="text-sm font-medium">Recent Sessions</div>
                                                                <div className="space-y-1">
                                                                    {course.recent_logs.map((log) => (
                                                                        <div
                                                                            key={log.id}
                                                                            className="flex items-center justify-between rounded-md bg-muted/50 px-3 py-2 text-sm"
                                                                        >
                                                                            <div className="flex items-center gap-2">
                                                                                {log.result ? (
                                                                                    <CheckCircle className="h-4 w-4 text-green-600" />
                                                                                ) : (
                                                                                    <div className="h-4 w-4 rounded-full border-2 border-red-600" />
                                                                                )}
                                                                                <span className="font-mono text-xs">{log.position}</span>
                                                                                <span className="text-muted-foreground">•</span>
                                                                                <span className="text-muted-foreground">
                                                                                    {new Date(log.session_date).toLocaleDateString('de')}
                                                                                </span>
                                                                            </div>
                                                                        </div>
                                                                    ))}
                                                                </div>
                                                            </div>
                                                        )}
                                                        <Button variant="outline" size="sm" className="w-full" onClick={() => handleViewLogs(course)}>
                                                            <Eye className="mr-2 h-4 w-4" />
                                                            View All Logs
                                                        </Button>
                                                    </CardContent>
                                                </Card>
                                            ))}
                                        </div>
                                    </CardContent>
                                </AccordionContent>
                            </Card>
                        </AccordionItem>
                    </Accordion>
                )}

                {/* Completed Courses */}
                {completedCourses && completedCourses.length > 0 && (
                    <Accordion type="single" collapsible defaultValue="">
                        <AccordionItem value="completed-courses">
                            <Card className="py-0">
                                <CardHeader className="gap-0 px-0 py-2">
                                    <AccordionTrigger className="w-full p-4 hover:no-underline [&[data-state=open]>div>svg]:rotate-180">
                                        <div className="flex items-center gap-2">
                                            <div className="rounded-full bg-green-100 p-2 dark:bg-green-900">
                                                <GraduationCap className="h-5 w-5 text-green-600 dark:text-green-400" />
                                            </div>
                                            <div className="text-left">
                                                <CardTitle>Completed Courses</CardTitle>
                                                <CardDescription>Successfully completed training</CardDescription>
                                            </div>
                                        </div>
                                    </AccordionTrigger>
                                </CardHeader>

                                <AccordionContent>
                                    <CardContent>
                                        <div className="mt-2 grid gap-4 md:grid-cols-2">
                                            {completedCourses.map((course) => (
                                                <Card key={course.id}>
                                                    <CardHeader className="pb-3">
                                                        <div className="flex items-start justify-between gap-3">
                                                            <div className="flex-1">
                                                                <CardTitle className="text-lg">{course.trainee_display_name}</CardTitle>
                                                                <div className="mt-1 flex items-center gap-2 text-sm text-muted-foreground">
                                                                    <MapPin className="h-3 w-3" />
                                                                    {course.airport_icao}
                                                                </div>
                                                            </div>
                                                            <Badge variant="outline" className={getTypeColor(course.position)}>
                                                                {course.position_display}
                                                            </Badge>
                                                        </div>
                                                    </CardHeader>
                                                    <CardContent className="space-y-3">
                                                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                                            <Calendar className="h-4 w-4" />
                                                            Completed {course.completed_at && new Date(course.completed_at).toLocaleDateString('de')}
                                                        </div>
                                                        <Button variant="outline" size="sm" className="w-full" onClick={() => handleViewLogs(course)}>
                                                            <Eye className="mr-2 h-4 w-4" />
                                                            View Training History
                                                        </Button>
                                                    </CardContent>
                                                </Card>
                                            ))}
                                        </div>
                                    </CardContent>
                                </AccordionContent>
                            </Card>
                        </AccordionItem>
                    </Accordion>
                )}

                {/* Moodle Courses */}
                {moodleCourses && moodleCourses.length > 0 && (
                    <Accordion type="single" collapsible defaultValue="moodle-courses">
                        <AccordionItem value="moodle-courses">
                            <Card className="py-0">
                                <CardHeader className="gap-0 px-0 py-2">
                                    <AccordionTrigger className="w-full p-4 hover:no-underline [&[data-state=open]>div>svg]:rotate-180">
                                        <div className="flex items-center gap-2">
                                            <div className="rounded-full bg-purple-100 p-2 dark:bg-purple-900">
                                                <BookOpen className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                            </div>
                                            <div className="text-left">
                                                <CardTitle>Moodle Courses</CardTitle>
                                                <CardDescription>E-learning courses for your training</CardDescription>
                                            </div>
                                        </div>
                                    </AccordionTrigger>
                                </CardHeader>

                                <AccordionContent>
                                    <CardContent>
                                        <div className="space-y-2">
                                            {moodleCourses.map((course) => (
                                                <div
                                                    key={course.id}
                                                    className="flex items-center justify-between rounded-lg border p-3 transition-colors hover:bg-muted/50"
                                                >
                                                    <div className="flex items-center gap-3">
                                                        {course.passed ? (
                                                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                                                                <CheckCircle className="h-5 w-5 text-green-600 dark:text-green-400" />
                                                            </div>
                                                        ) : (
                                                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900">
                                                                <BookOpen className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                                            </div>
                                                        )}
                                                        <div>
                                                            <div className="font-medium">{course.name}</div>
                                                            <div className="text-sm text-muted-foreground">
                                                                {course.passed ? 'Completed' : 'In Progress'}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <Button variant="ghost" size="sm" asChild>
                                                        <a href={course.link} target="_blank" rel="noopener noreferrer">
                                                            <ExternalLink className="h-4 w-4" />
                                                        </a>
                                                    </Button>
                                                </div>
                                            ))}
                                        </div>
                                    </CardContent>
                                </AccordionContent>
                            </Card>
                        </AccordionItem>
                    </Accordion>
                )}

                {/* Familiarisations */}
                {familiarisations && Object.keys(familiarisations).length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <div className="rounded-full bg-orange-100 p-2 dark:bg-orange-900">
                                    <Map className="h-5 w-5 text-orange-600 dark:text-orange-400" />
                                </div>
                                Centre Familiarisations
                            </CardTitle>
                            <CardDescription>Your familiarised sectors</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {Object.entries(familiarisations).map(([fir, sectors]) => (
                                    <div key={fir}>
                                        <div className="mb-2 font-semibold">{fir}</div>
                                        <div className="flex flex-wrap gap-2">
                                            {sectors.map((sector) => (
                                                <Badge key={sector.id} variant="outline" className="font-mono">
                                                    {sector.sector_name}
                                                </Badge>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Empty State */}
                {(!activeCourses || activeCourses.length === 0) &&
                    (!completedCourses || completedCourses.length === 0) &&
                    (!moodleCourses || moodleCourses.length === 0) && (
                        <Card className="py-12">
                            <CardContent className="flex flex-col items-center text-center">
                                <BookOpen className="mb-4 h-16 w-16 text-muted-foreground" />
                                <h3 className="mb-2 text-lg font-semibold">Welcome to Your Dashboard</h3>
                                <p className="mb-4 text-muted-foreground">
                                    You don't have any active courses yet. Browse available courses to start your training journey.
                                </p>
                                <Button asChild>
                                    <a href={route('courses.index')}>Browse Courses</a>
                                </Button>
                            </CardContent>
                        </Card>
                    )}
            </div>

            <CourseLogsModal course={selectedCourse} isOpen={logsModalOpen} onClose={() => setLogsModalOpen(false)} />
        </AppLayout>
    );
}
