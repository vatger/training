import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';
import {
    User,
    BookOpen,
    Award,
    Map,
    GraduationCap,
    Shield,
    Clock,
    Calendar,
    AlertCircle,
    CheckCircle,
    ExternalLink,
    XCircle,
    Eye,
    Plane,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { getPositionIcon, getTypeColor } from '@/lib/course-utils';

interface UserProfile {
    vatsim_id: number;
    first_name: string;
    last_name: string;
    email?: string;
    rating: number;
    subdivision?: string;
    last_rating_change?: string;
    is_mentor: boolean;
    is_superuser: boolean;
    is_admin: boolean;
    solo_days_used: number;
}

interface Course {
    id: number;
    name: string;
    type: string;
    position: string;
    is_mentor: boolean;
    logs?: TrainingLog[];
    completed_at?: string | null;
    total_sessions?: number;
}

interface TrainingLog {
    id: number;
    session_date: string;
    position: string;
    type: string;
    type_display: string;
    result: boolean;
    mentor_name: string;
    session_duration?: number;
    next_step?: string | null;
    average_rating?: number | null;
}

interface Endorsement {
    position: string;
    activity_hours: number;
    status: string;
    last_activity_date?: string;
}

interface Familiarisation {
    id: number;
    sector_name: string;
    fir: string;
}

interface MoodleCourse {
    id: number;
    name: string;
    passed: boolean;
    link: string;
}

interface UserData {
    user: UserProfile;
    active_courses: Course[];
    completed_courses: Course[];
    endorsements: Endorsement[];
    moodle_courses: MoodleCourse[];
    familiarisations: Record<string, Familiarisation[]>;
}

const getRatingDisplay = (rating: number): string => {
    const ratings: Record<number, string> = {
        0: 'Suspended',
        1: 'Observer (OBS)',
        2: 'Student 1 (S1)',
        3: 'Student 2 (S2)',
        4: 'Student 3 (S3)',
        5: 'Controller 1 (C1)',
        7: 'Controller 3 (C3)',
        8: 'Instructor 1 (I1)',
        10: 'Instructor 3 (I3)',
        11: 'Supervisor (SUP)',
        12: 'Administrator (ADM)',
    };
    return ratings[rating] || 'Unknown';
};

const getSessionTypeColor = (type: string) => {
    switch (type) {
        case 'O':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
        case 'S':
            return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
        case 'L':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
    }
};

const getStatusBadge = (status: string) => {
    switch (status) {
        case 'active':
            return (
                <Badge
                    variant="outline"
                    className="border-green-200 bg-green-50 text-green-700 dark:border-green-700 dark:bg-green-900 dark:text-green-300"
                >
                    <CheckCircle className="mr-1 h-3 w-3" />
                    Active
                </Badge>
            );
        case 'warning':
            return (
                <Badge
                    variant="outline"
                    className="border-yellow-200 bg-yellow-50 text-yellow-700 dark:border-yellow-700 dark:bg-yellow-900 dark:text-yellow-300"
                >
                    <AlertCircle className="mr-1 h-3 w-3" />
                    Low Activity
                </Badge>
            );
        case 'removal':
            return (
                <Badge variant="outline" className="border-red-200 bg-red-50 text-red-700 dark:border-red-700 dark:bg-red-900 dark:text-red-300">
                    <AlertCircle className="mr-1 h-3 w-3" />
                    Removal Pending
                </Badge>
            );
        default:
            return <Badge variant="outline">{status}</Badge>;
    }
};

export default function UserProfilePage({ userData }: { userData: UserData }) {
    const { user, active_courses, completed_courses, endorsements, moodle_courses, familiarisations } = userData;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Dashboard',
            href: dashboard().url,
        },
        {
            title: 'Find User',
            href: '#',
        },
        {
            title: `${user.first_name} ${user.last_name}`,
            href: `/users/${user.vatsim_id}`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${user.first_name} ${user.last_name} - User Profile`} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <div className="flex items-start justify-between">
                            <div className="flex items-center gap-4">
                                <div className="flex h-16 w-16 items-center justify-center rounded-full bg-primary/10">
                                    <User className="h-8 w-8 text-primary" />
                                </div>
                                <div>
                                    <div className="flex items-center gap-3">
                                        <CardTitle className="text-2xl">
                                            {user.first_name} {user.last_name}
                                        </CardTitle>
                                        <div className="flex gap-2">
                                            <Button variant="outline" size="sm" asChild>
                                                <a
                                                    href={`https://stats.vatsim.net/stats/${user.vatsim_id}`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="flex items-center gap-2"
                                                >
                                                    VATSIM Stats
                                                    <ExternalLink className="h-3 w-3" />
                                                </a>
                                            </Button>
                                            <Button variant="outline" size="sm" asChild>
                                                <a
                                                    href={`https://core.vateud.net/manage/controller/${user.vatsim_id}/view`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="flex items-center gap-2"
                                                >
                                                    VATEUD Core
                                                    <ExternalLink className="h-3 w-3" />
                                                </a>
                                            </Button>
                                        </div>
                                    </div>
                                    <CardDescription className="mt-1 flex flex-wrap items-center gap-3">
                                        <span>VATSIM ID: {user.vatsim_id}</span>
                                    </CardDescription>
                                </div>
                            </div>
                            <div className="flex flex-col gap-2">
                                {(user.is_admin || user.vatsim_id === 1601613) && <Badge variant="destructive">Administrator</Badge>}
                                {user.is_superuser && user.vatsim_id !== 1601613 && <Badge variant="default">ATD Leadership</Badge>}
                                {user.is_mentor && <Badge variant="secondary">Mentor</Badge>}
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div className="flex items-center gap-3 rounded-lg border p-3">
                                <Award className="h-5 w-5 text-muted-foreground" />
                                <div>
                                    <p className="text-sm font-medium">Rating</p>
                                    <p className="text-xs text-muted-foreground">{getRatingDisplay(user.rating)}</p>
                                </div>
                            </div>
                            {user.subdivision && (
                                <div className="flex items-center gap-3 rounded-lg border p-3">
                                    <Map className="h-5 w-5 text-muted-foreground" />
                                    <div>
                                        <p className="text-sm font-medium">Subdivision</p>
                                        <p className="text-xs text-muted-foreground">{user.subdivision}</p>
                                    </div>
                                </div>
                            )}
                            {user.last_rating_change && (
                                <div className="flex items-center gap-3 rounded-lg border p-3">
                                    <Calendar className="h-5 w-5 text-muted-foreground" />
                                    <div>
                                        <p className="text-sm font-medium">Last Rating Change</p>
                                        <p className="text-xs text-muted-foreground">{new Date(user.last_rating_change).toLocaleDateString('de')}</p>
                                    </div>
                                </div>
                            )}
                            {user.solo_days_used > 0 && (
                                <div className="flex items-center gap-3 rounded-lg border p-3">
                                    <Plane className="h-5 w-5 text-muted-foreground" />
                                    <div className="w-full">
                                        <p className="text-sm font-medium">Solo Days</p>
                                        <div className="mt-1 flex items-center gap-2">
                                            <p className="text-xs text-muted-foreground">{user.solo_days_used} / 90 used</p>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>

                <Tabs defaultValue="active-courses" className="w-full">
                    <TabsList className="grid w-full grid-cols-5">
                        <TabsTrigger value="active-courses">
                            <BookOpen className="mr-2 h-4 w-4" />
                            Active ({active_courses.length})
                        </TabsTrigger>
                        <TabsTrigger value="completed-courses">
                            <GraduationCap className="mr-2 h-4 w-4" />
                            Completed ({completed_courses.length})
                        </TabsTrigger>
                        <TabsTrigger value="endorsements">
                            <Shield className="mr-2 h-4 w-4" />
                            Endorsements ({endorsements.length})
                        </TabsTrigger>
                        <TabsTrigger value="moodle">
                            <GraduationCap className="mr-2 h-4 w-4" />
                            Moodle ({moodle_courses.length})
                        </TabsTrigger>
                        <TabsTrigger value="familiarisations">
                            <Map className="mr-2 h-4 w-4" />
                            Familiarisations
                        </TabsTrigger>
                    </TabsList>
                    <TabsContent value="active-courses" className="mt-4 space-y-4">
                        {active_courses.length > 0 ? (
                            <Accordion type="multiple" className="w-full space-y-4">
                                {active_courses.map((course) => {
                                    const hasLogs = course.is_mentor && course.logs && course.logs.length > 0;

                                    return (
                                        <AccordionItem key={course.id} value={`course-${course.id}`} className="border-none">
                                            <Card className="py-0">
                                                <CardHeader>
                                                    <AccordionTrigger className="hover:no-underline [&[data-state=open]>div>svg]:rotate-180">
                                                        <div className="flex w-full items-start justify-between pr-4">
                                                            <div className="flex items-center gap-3">
                                                                {getPositionIcon(course.position)}
                                                                <div className="text-left">
                                                                    <CardTitle className="text-base">{course.name}</CardTitle>
                                                                    <CardDescription className="mt-1 flex flex-wrap gap-2">
                                                                        <Badge variant="outline">{course.position}</Badge>
                                                                        <Badge className={getTypeColor(course.type)}>{course.type}</Badge>
                                                                        {hasLogs && (
                                                                            <Badge variant="secondary" className="text-xs">
                                                                                {course.logs?.length} log{course.logs?.length !== 1 ? 's' : ''}
                                                                            </Badge>
                                                                        )}
                                                                    </CardDescription>
                                                                </div>
                                                            </div>
                                                            <div className="flex items-center gap-2">
                                                                {!course.is_mentor && (
                                                                    <Badge variant="secondary" className="text-xs">
                                                                        View Only
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </AccordionTrigger>
                                                </CardHeader>
                                                <AccordionContent>
                                                    <CardContent className="pt-0">
                                                        {course.is_mentor ? (
                                                            hasLogs ? (
                                                                <div className="space-y-3">
                                                                    <div className="flex items-center justify-between border-t pt-4">
                                                                        <h4 className="text-sm font-semibold">Training History</h4>
                                                                    </div>
                                                                    <div className="relative space-y-6 pl-8 before:absolute before:top-0 before:bottom-0 before:left-4 before:w-0.5 before:bg-border">
                                                                        {course.logs?.map((log) => (
                                                                            <div key={log.id} className="relative">
                                                                                <div
                                                                                    className={cn(
                                                                                        'absolute -left-[23px] mt-1.5 h-4 w-4 rounded-full border-2 border-background',
                                                                                        log.result ? 'bg-green-500' : 'bg-red-500',
                                                                                    )}
                                                                                />

                                                                                <div className="rounded-lg border bg-card p-4 shadow-sm transition-shadow hover:shadow-md">
                                                                                    <div className="mb-3 flex items-start justify-between">
                                                                                        <div className="flex-1">
                                                                                            <div className="mb-2 flex flex-wrap items-center gap-2">
                                                                                                <Badge
                                                                                                    variant="outline"
                                                                                                    className={getSessionTypeColor(log.type)}
                                                                                                >
                                                                                                    {log.type_display}
                                                                                                </Badge>
                                                                                                <Badge
                                                                                                    variant={log.result ? 'default' : 'destructive'}
                                                                                                    className="flex items-center gap-1"
                                                                                                >
                                                                                                    {log.result ? (
                                                                                                        <>
                                                                                                            <CheckCircle className="h-3 w-3" />
                                                                                                            Passed
                                                                                                        </>
                                                                                                    ) : (
                                                                                                        <>
                                                                                                            <XCircle className="h-3 w-3" />
                                                                                                            Not Passed
                                                                                                        </>
                                                                                                    )}
                                                                                                </Badge>
                                                                                            </div>
                                                                                            <h4 className="font-semibold">{log.position}</h4>
                                                                                            <div className="mt-2 flex items-center gap-4 text-sm text-muted-foreground">
                                                                                                <span className="flex items-center gap-1">
                                                                                                    <Calendar className="h-3 w-3" />
                                                                                                    {new Date(log.session_date).toLocaleDateString(
                                                                                                        'de',
                                                                                                    )}
                                                                                                </span>
                                                                                                {log.session_duration && (
                                                                                                    <span className="flex items-center gap-1">
                                                                                                        <Clock className="h-3 w-3" />
                                                                                                        {log.session_duration} min
                                                                                                    </span>
                                                                                                )}
                                                                                            </div>
                                                                                        </div>
                                                                                        <Link href={route('training-logs.show', log.id)}>
                                                                                            <Button size="sm" variant="ghost">
                                                                                                <Eye className="h-4 w-4" />
                                                                                            </Button>
                                                                                        </Link>
                                                                                    </div>

                                                                                    {log.next_step && (
                                                                                        <div className="mt-3 rounded-md bg-muted/50 p-3">
                                                                                            <p className="mb-1 text-sm font-medium text-muted-foreground">
                                                                                                Next Step:
                                                                                            </p>
                                                                                            <p className="text-sm">{log.next_step}</p>
                                                                                        </div>
                                                                                    )}

                                                                                    <div className="mt-3 text-xs text-muted-foreground">
                                                                                        Mentor: {log.mentor_name}
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        ))}
                                                                    </div>
                                                                </div>
                                                            ) : (
                                                                <Alert className="border-t">
                                                                    <AlertCircle className="h-4 w-4" />
                                                                    <AlertDescription>No training logs yet for this course</AlertDescription>
                                                                </Alert>
                                                            )
                                                        ) : (
                                                            <Alert className="border-t">
                                                                <AlertCircle className="h-4 w-4" />
                                                                <AlertDescription>
                                                                    Training logs are only visible to mentors of this course
                                                                </AlertDescription>
                                                            </Alert>
                                                        )}
                                                    </CardContent>
                                                </AccordionContent>
                                            </Card>
                                        </AccordionItem>
                                    );
                                })}
                            </Accordion>
                        ) : (
                            <Card>
                                <CardContent className="flex flex-col items-center justify-center py-12">
                                    <BookOpen className="mb-4 h-12 w-12 text-muted-foreground" />
                                    <h3 className="mb-2 text-lg font-semibold">No Active Courses</h3>
                                    <p className="text-sm text-muted-foreground">This user is not currently enrolled in any courses</p>
                                </CardContent>
                            </Card>
                        )}
                    </TabsContent>
                    <TabsContent value="completed-courses" className="mt-4 space-y-4">
                        {completed_courses.length > 0 ? (
                            <Accordion type="multiple" className="w-full space-y-4">
                                {completed_courses.map((course) => {
                                    const hasLogs = course.total_sessions && course.total_sessions > 0;

                                    return (
                                        <AccordionItem key={course.id} value={`completed-course-${course.id}`} className="border-none">
                                            <Card className="py-0">
                                                <CardHeader>
                                                    <AccordionTrigger className="hover:no-underline [&[data-state=open]>div>svg]:rotate-180">
                                                        <div className="flex w-full items-start justify-between pr-4">
                                                            <div className="flex items-center gap-3">
                                                                {getPositionIcon(course.position)}
                                                                <div className="text-left">
                                                                    <CardTitle className="text-base">{course.name}</CardTitle>
                                                                    <CardDescription className="mt-1 flex flex-wrap gap-2">
                                                                        <Badge variant="outline">{course.position}</Badge>
                                                                        <Badge className={getTypeColor(course.type)}>{course.type}</Badge>
                                                                        {hasLogs && (
                                                                            <Badge variant="secondary" className="text-xs">
                                                                                {course.logs?.length} log{course.logs?.length !== 1 ? 's' : ''}
                                                                            </Badge>
                                                                        )}
                                                                    </CardDescription>
                                                                </div>
                                                            </div>
                                                            <div className="flex items-center gap-2">
                                                                {!course.is_mentor && (
                                                                    <Badge variant="secondary" className="text-xs">
                                                                        View Only
                                                                    </Badge>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </AccordionTrigger>
                                                </CardHeader>
                                                <AccordionContent>
                                                    <CardContent className="pt-0">
                                                        {course.is_mentor ? (
                                                            hasLogs ? (
                                                                <div className="space-y-3">
                                                                    <div className="flex items-center justify-between border-t pt-4">
                                                                        <h4 className="text-sm font-semibold">Training History</h4>
                                                                    </div>
                                                                    <div className="relative space-y-6 pl-8 before:absolute before:top-0 before:bottom-0 before:left-4 before:w-0.5 before:bg-border">
                                                                        {course.logs?.map((log) => (
                                                                            <div key={log.id} className="relative">
                                                                                <div
                                                                                    className={cn(
                                                                                        'absolute -left-[23px] mt-1.5 h-4 w-4 rounded-full border-2 border-background',
                                                                                        log.result ? 'bg-green-500' : 'bg-red-500',
                                                                                    )}
                                                                                />

                                                                                <div className="rounded-lg border bg-card p-4 shadow-sm transition-shadow hover:shadow-md">
                                                                                    <div className="mb-3 flex items-start justify-between">
                                                                                        <div className="flex-1">
                                                                                            <div className="mb-2 flex flex-wrap items-center gap-2">
                                                                                                <Badge
                                                                                                    variant="outline"
                                                                                                    className={getSessionTypeColor(log.type)}
                                                                                                >
                                                                                                    {log.type_display}
                                                                                                </Badge>
                                                                                                <Badge
                                                                                                    variant={log.result ? 'default' : 'destructive'}
                                                                                                    className="flex items-center gap-1"
                                                                                                >
                                                                                                    {log.result ? (
                                                                                                        <>
                                                                                                            <CheckCircle className="h-3 w-3" />
                                                                                                            Passed
                                                                                                        </>
                                                                                                    ) : (
                                                                                                        <>
                                                                                                            <XCircle className="h-3 w-3" />
                                                                                                            Not Passed
                                                                                                        </>
                                                                                                    )}
                                                                                                </Badge>
                                                                                            </div>
                                                                                            <h4 className="font-semibold">{log.position}</h4>
                                                                                            <div className="mt-2 flex items-center gap-4 text-sm text-muted-foreground">
                                                                                                <span className="flex items-center gap-1">
                                                                                                    <Calendar className="h-3 w-3" />
                                                                                                    {new Date(log.session_date).toLocaleDateString(
                                                                                                        'de',
                                                                                                    )}
                                                                                                </span>
                                                                                                {log.session_duration && (
                                                                                                    <span className="flex items-center gap-1">
                                                                                                        <Clock className="h-3 w-3" />
                                                                                                        {log.session_duration} min
                                                                                                    </span>
                                                                                                )}
                                                                                            </div>
                                                                                        </div>
                                                                                        <Link href={route('training-logs.show', log.id)}>
                                                                                            <Button size="sm" variant="ghost">
                                                                                                <Eye className="h-4 w-4" />
                                                                                            </Button>
                                                                                        </Link>
                                                                                    </div>

                                                                                    {log.next_step && (
                                                                                        <div className="mt-3 rounded-md bg-muted/50 p-3">
                                                                                            <p className="mb-1 text-sm font-medium text-muted-foreground">
                                                                                                Next Step:
                                                                                            </p>
                                                                                            <p className="text-sm">{log.next_step}</p>
                                                                                        </div>
                                                                                    )}

                                                                                    <div className="mt-3 text-xs text-muted-foreground">
                                                                                        Mentor: {log.mentor_name}
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        ))}
                                                                    </div>
                                                                </div>
                                                            ) : (
                                                                <Alert className="border-t">
                                                                    <AlertCircle className="h-4 w-4" />
                                                                    <AlertDescription>No training logs yet for this course</AlertDescription>
                                                                </Alert>
                                                            )
                                                        ) : (
                                                            <Alert className="border-t">
                                                                <AlertCircle className="h-4 w-4" />
                                                                <AlertDescription>
                                                                    Training logs are only visible to mentors of this course
                                                                </AlertDescription>
                                                            </Alert>
                                                        )}
                                                    </CardContent>
                                                </AccordionContent>
                                            </Card>
                                        </AccordionItem>
                                    );
                                })}
                            </Accordion>
                        ) : (
                            <Card>
                                <CardContent className="flex flex-col items-center justify-center py-12">
                                    <GraduationCap className="mb-4 h-12 w-12 text-muted-foreground" />
                                    <h3 className="mb-2 text-lg font-semibold">No Completed Courses</h3>
                                    <p className="text-sm text-muted-foreground">This user hasn't completed any courses yet</p>
                                </CardContent>
                            </Card>
                        )}
                    </TabsContent>
                    <TabsContent value="endorsements" className="mt-4 space-y-4">
                        {endorsements.length > 0 ? (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Active Endorsements</CardTitle>
                                    <CardDescription>Position-specific endorsements and their activity status</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        {endorsements.map((endorsement, idx) => (
                                            <div key={idx} className="flex items-center justify-between rounded-lg border p-4">
                                                <div className="flex items-center gap-3">
                                                    <Shield className="h-5 w-5 text-muted-foreground" />
                                                    <div>
                                                        <p className="font-medium">{endorsement.position}</p>
                                                        <div className="mt-1 flex items-center gap-2">
                                                            <Clock className="h-3 w-3 text-muted-foreground" />
                                                            <span className="text-xs text-muted-foreground">
                                                                {endorsement.activity_hours}h activity
                                                            </span>
                                                            {endorsement.last_activity_date && (
                                                                <>
                                                                    <span className="text-xs text-muted-foreground">•</span>
                                                                    <span className="text-xs text-muted-foreground">
                                                                        Last: {new Date(endorsement.last_activity_date).toLocaleDateString('de')}
                                                                    </span>
                                                                </>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                                {getStatusBadge(endorsement.status)}
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        ) : (
                            <Card>
                                <CardContent className="flex flex-col items-center justify-center py-12">
                                    <Shield className="mb-4 h-12 w-12 text-muted-foreground" />
                                    <h3 className="mb-2 text-lg font-semibold">No Active Endorsements</h3>
                                    <p className="text-sm text-muted-foreground">This user doesn't have any active endorsements</p>
                                </CardContent>
                            </Card>
                        )}
                    </TabsContent>
                    <TabsContent value="moodle" className="mt-4 space-y-4">
                        {moodle_courses.length > 0 ? (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Moodle Courses</CardTitle>
                                    <CardDescription>Online training courses and completion status</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-3">
                                        {moodle_courses.map((course) => (
                                            <div key={course.id} className="flex items-center justify-between rounded-lg border p-4">
                                                <div className="flex items-center gap-3">
                                                    <GraduationCap className="h-5 w-5 text-muted-foreground" />
                                                    <div>
                                                        <p className="font-medium">{course.name}</p>
                                                        <p className="text-xs text-muted-foreground">Course ID: {course.id}</p>
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-3">
                                                    {course.passed ? (
                                                        <Badge
                                                            variant="outline"
                                                            className="border-green-200 bg-green-50 text-green-700 dark:border-green-700 dark:bg-green-900 dark:text-green-300"
                                                        >
                                                            <CheckCircle className="mr-1 h-3 w-3" />
                                                            Completed
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="outline" className="border-yellow-200 bg-yellow-50 text-yellow-700">
                                                            <Clock className="mr-1 h-3 w-3" />
                                                            In Progress
                                                        </Badge>
                                                    )}
                                                    <a
                                                        href={course.link}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="text-sm text-primary hover:underline"
                                                    >
                                                        View Course →
                                                    </a>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        ) : (
                            <Card>
                                <CardContent className="flex flex-col items-center justify-center py-12">
                                    <GraduationCap className="mb-4 h-12 w-12 text-muted-foreground" />
                                    <h3 className="mb-2 text-lg font-semibold">No Moodle Courses</h3>
                                    <p className="text-sm text-muted-foreground">This user doesn't have any assigned Moodle courses</p>
                                </CardContent>
                            </Card>
                        )}
                    </TabsContent>
                    <TabsContent value="familiarisations" className="mt-4 space-y-4">
                        {Object.keys(familiarisations).length > 0 ? (
                            <div className="space-y-4">
                                {Object.entries(familiarisations).map(([fir, fams]) => (
                                    <Card key={fir}>
                                        <CardHeader>
                                            <CardTitle className="flex items-center gap-2">
                                                <Map className="h-5 w-5" />
                                                {fir}
                                            </CardTitle>
                                            <CardDescription>{fams.length} sector(s) familiarised</CardDescription>
                                        </CardHeader>
                                        <CardContent>
                                            <div className="flex flex-wrap gap-2">
                                                {fams.map((fam) => (
                                                    <Badge key={fam.id} variant="outline" className="text-sm">
                                                        {fam.sector_name}
                                                    </Badge>
                                                ))}
                                            </div>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        ) : (
                            <Card>
                                <CardContent className="flex flex-col items-center justify-center py-12">
                                    <Map className="mb-4 h-12 w-12 text-muted-foreground" />
                                    <h3 className="mb-2 text-lg font-semibold">No Familiarisations</h3>
                                    <p className="text-sm text-muted-foreground">This user hasn't completed any sector familiarisations</p>
                                </CardContent>
                            </Card>
                        )}
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}