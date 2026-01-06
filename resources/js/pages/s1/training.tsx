import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { AlertCircle, Calendar, CheckCircle2, Clock, ExternalLink, Users } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

interface WaitingListInfo {
    id: number;
    position: number;
    total_waiting: number;
    joined_at: string;
    expires_at: string | null;
    needs_confirmation: boolean;
    confirmation_due_at: string | null;
}

interface SessionInfo {
    id: number;
    scheduled_at: string;
    mentor_name: string;
    language: string;
    max_trainees: number;
    total_signups: number;
    available_spots: number;
    signups_locked: boolean;
    notes?: string | null;
    user_signed_up: boolean;
    user_selected: boolean;
    user_waiting_position?: number;
}

interface QuizCompletion {
    completed: number;
    total: number;
    percentage: number;
    quizzes: Array<{
        id: number;
        completed: boolean;
        name: string;
        url?: string;
    }>;
}

interface ModuleProgress {
    id: number;
    name: string;
    sequence_order: number;
    status: 'completed' | 'waiting' | 'locked';
    completed_at?: string;
    waiting_list?: WaitingListInfo | null;
    available_sessions?: SessionInfo[];
    quiz_completion?: QuizCompletion | null;
    has_selected_session?: boolean;
    has_any_signup?: boolean;
}

interface CurrentStep {
    type: string;
    module?: ModuleProgress;
    session?: SessionInfo;
    title: string;
    description: string;
    action?: string;
    action_type?: string;
    action_url?: string;
    action_data?: {
        module_id?: number;
        session_id?: number;
        waiting_list_id?: number;
    };
    action_variant?: string;
    progress?: number;
    waiting_position?: number;
    needs_confirmation?: boolean;
    confirmation_data?: {
        waiting_list_id: number;
    } | null;
}

interface Progress {
    overallPercentage: number;
    currentStepNumber: number;
    totalSteps: number;
    modules: ModuleProgress[];
    canUpgrade: boolean;
}

interface Props {
    isVatsimUser: boolean;
    currentStep: CurrentStep | null;
    progress: Progress | null;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'S1 Training',
        href: route('s1.training'),
    },
];

export default function S1Training({ isVatsimUser, currentStep, progress }: Props) {
    const { flash } = usePage<{ flash: { success?: string; error?: string } }>().props;
    const [leaveDialogOpen, setLeaveDialogOpen] = useState(false);
    const [moduleToLeave, setModuleToLeave] = useState<number | null>(null);

    const joinWaitingListForm = useForm({
        confirm_requirements: true,
    });

    useEffect(() => {
        if (flash.success) {
            toast.success(flash.success);
        }
        if (flash.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    const handleAction = () => {
        if (!currentStep || !currentStep.action_type) return;

        if (currentStep.action_type === 'external' && currentStep.action_url) {
            window.open(currentStep.action_url, '_blank');
            return;
        }

        if (currentStep.action_type === 'post') {
            if (currentStep.type === 'join_waiting_list' && currentStep.action_data?.module_id) {
                joinWaitingListForm.post(`/s1/waiting-list/${currentStep.action_data.module_id}/join`, {
                    preserveScroll: true,
                    onSuccess: () => {
                        joinWaitingListForm.reset();
                    },
                });
            } else if (currentStep.type === 'on_waiting_list' && currentStep.action_data?.module_id) {
                setModuleToLeave(currentStep.action_data.module_id);
                setLeaveDialogOpen(true);
            } else if (currentStep.type === 'signup_available' && currentStep.action_data?.session_id) {
                router.post(
                    `/s1/session/${currentStep.action_data.session_id}/signup`,
                    {},
                    {
                        preserveScroll: true,
                    },
                );
            } else if (currentStep.type === 'request_upgrade') {
                router.post(
                    '/s1/request-upgrade',
                    {},
                    {
                        preserveScroll: true,
                    },
                );
            }
        }
    };

    const handleSessionSignup = (sessionId: number) => {
        router.post(
            `/s1/session/${sessionId}/signup`,
            {},
            {
                preserveScroll: true,
            },
        );
    };

    const handleConfirmWaitingList = (waitingListId: number) => {
        router.post(
            `/s1/waiting-list/${waitingListId}/confirm`,
            {},
            {
                preserveScroll: true,
            },
        );
    };

    const handleCancelSignup = (sessionId: number) => {
        router.post(
            `/s1/session/${sessionId}/cancel`,
            {},
            {
                preserveScroll: true,
            },
        );
    };

    const handleLeaveWaitingList = (moduleId: number) => {
        setModuleToLeave(moduleId);
        setLeaveDialogOpen(true);
    };

    const confirmLeaveWaitingList = () => {
        if (moduleToLeave) {
            router.post(
                `/s1/waiting-list/${moduleToLeave}/leave`,
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        setLeaveDialogOpen(false);
                        setModuleToLeave(null);
                    },
                },
            );
        }
    };

    if (!isVatsimUser) {
        return (
            <AppLayout>
                <Head title="S1 Training" />
                <div className="py-12">
                    <div className="mx-auto max-w-4xl sm:px-6 lg:px-8">
                        <Alert variant="destructive">
                            <AlertCircle className="h-4 w-4" />
                            <AlertTitle>VATSIM Account Required</AlertTitle>
                            <AlertDescription>
                                S1 Training is only available for VATSIM users. Please ensure your account is properly linked.
                            </AlertDescription>
                        </Alert>
                    </div>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="S1 Training" />

            <AlertDialog open={leaveDialogOpen} onOpenChange={setLeaveDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Leave Waiting List?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to leave the waiting list? You will lose your current position and will need to rejoin at the back
                            of the queue if you change your mind.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction onClick={confirmLeaveWaitingList} className="bg-red-600 hover:bg-red-700">
                            Leave Waiting List
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                {progress && (
                    <Card>
                        <CardHeader>
                            <div>
                                <h1 className="text-3xl font-bold">S1 Training Journey</h1>
                                <p className="mt-2 text-muted-foreground">Complete the modules step-by-step to earn your S1 rating</p>
                            </div>
                            <CardDescription>
                                {progress.modules.filter((m) => m.status === 'completed').length} of {progress.modules.length} modules completed
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Progress value={progress.overallPercentage} className="h-3" />
                            <p className="mt-2 text-sm text-muted-foreground">{progress.overallPercentage}% complete</p>
                        </CardContent>
                    </Card>
                )}

                {currentStep && (
                    <Card className="border-primary shadow-lg">
                        <CardHeader>
                            <div className="flex items-start justify-between">
                                <div className="flex-1">
                                    <CardTitle className="text-2xl">{currentStep.title}</CardTitle>
                                    <CardDescription className="mt-2 text-base">{currentStep.description}</CardDescription>
                                </div>
                                <Badge variant="default" className="ml-4 shrink-0">
                                    Current Step
                                </Badge>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {currentStep.type === 'complete_quizzes' && currentStep.progress !== undefined && (
                                <div>
                                    <Progress value={currentStep.progress} className="h-2" />
                                    <p className="mt-1 text-sm text-muted-foreground">{currentStep.progress}% complete</p>
                                </div>
                            )}

                            {currentStep.type === 'signup_available' && currentStep.session && (
                                <Alert>
                                    <Calendar className="h-4 w-4" />
                                    <AlertTitle>Session Details</AlertTitle>
                                    <AlertDescription>
                                        <div className="mt-2 space-y-1">
                                            <p>
                                                <strong>Date:</strong> {new Date(currentStep.session.scheduled_at).toLocaleString('de')}
                                            </p>
                                            <p>
                                                <strong>Mentor:</strong> {currentStep.session.mentor_name}
                                            </p>
                                            <p>
                                                <strong>Language:</strong> {currentStep.session.language}
                                            </p>
                                            <p>
                                                <strong>Available Spots:</strong> {currentStep.session.available_spots} /{' '}
                                                {currentStep.session.max_trainees}
                                            </p>
                                            {currentStep.waiting_position && (
                                                <p className="mt-2 text-sm text-muted-foreground">
                                                    Your waiting list position: #{currentStep.waiting_position}
                                                </p>
                                            )}
                                        </div>
                                    </AlertDescription>
                                </Alert>
                            )}

                            {currentStep.type === 'confirmed_session' && currentStep.session && (
                                <Alert className="border-green-500 bg-green-50 dark:bg-green-950">
                                    <CheckCircle2 className="h-4 w-4 text-green-600" />
                                    <AlertTitle className="text-green-600">Session Confirmed</AlertTitle>
                                    <AlertDescription>
                                        <p className="mt-2 text-green-700 dark:text-green-300">
                                            <strong>Date:</strong> {new Date(currentStep.session.scheduled_at).toLocaleString('de')}
                                        </p>
                                        <p className="text-green-700 dark:text-green-300">
                                            <strong>Mentor:</strong> {currentStep.session.mentor_name}
                                        </p>
                                    </AlertDescription>
                                </Alert>
                            )}

                            {currentStep.type === 'pending_selection' && currentStep.session && (
                                <Alert>
                                    <Clock className="h-4 w-4" />
                                    <AlertTitle>Selection Pending</AlertTitle>
                                    <AlertDescription>
                                        <p className="mt-2">Session: {new Date(currentStep.session.scheduled_at).toLocaleString('de')}</p>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            Participants will be selected based on waiting list position approximately 48 hours before the session.
                                        </p>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => handleCancelSignup(currentStep.session!.id)}
                                            className="mt-3"
                                        >
                                            Cancel Signup
                                        </Button>
                                    </AlertDescription>
                                </Alert>
                            )}

                            {currentStep.type === 'on_waiting_list' && currentStep.module?.waiting_list && (
                                <Alert>
                                    <Users className="h-4 w-4" />
                                    <AlertTitle>Waiting List Status</AlertTitle>
                                    <AlertDescription>
                                        <p className="mt-2">
                                            Position: #{currentStep.module.waiting_list.position} of {currentStep.module.waiting_list.total_waiting}
                                        </p>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            You'll be notified when training sessions become available. Sessions select participants from the top of
                                            the waiting list.
                                        </p>
                                        {currentStep.needs_confirmation && currentStep.confirmation_data && (
                                            <div className="mt-3">
                                                <p className="mb-2 font-medium text-yellow-600 dark:text-yellow-400">
                                                    ⚠️ Confirmation required by {currentStep.module.waiting_list.confirmation_due_at}
                                                </p>
                                                <Button
                                                    variant="default"
                                                    size="sm"
                                                    onClick={() => handleConfirmWaitingList(currentStep.confirmation_data!.waiting_list_id)}
                                                >
                                                    Confirm Position
                                                </Button>
                                            </div>
                                        )}
                                    </AlertDescription>
                                </Alert>
                            )}

                            {currentStep.action && (
                                <Button
                                    onClick={handleAction}
                                    size="lg"
                                    className="w-full sm:w-auto"
                                    variant={currentStep.action_variant === 'destructive' ? 'destructive' : 'default'}
                                    disabled={joinWaitingListForm.processing}
                                >
                                    {joinWaitingListForm.processing ? 'Processing...' : currentStep.action}
                                    {currentStep.action.includes('Moodle') && <ExternalLink className="ml-2 h-4 w-4" />}
                                </Button>
                            )}

                            {currentStep.type === 'signup_available' &&
                                currentStep.module?.available_sessions &&
                                currentStep.module.available_sessions.length > 1 && (
                                    <div className="mt-4">
                                        <h4 className="mb-3 text-sm font-medium">Or choose from other available sessions:</h4>
                                        <div className="space-y-2">
                                            {currentStep.module.available_sessions.slice(1).map((session) => (
                                                <div key={session.id} className="flex items-center justify-between rounded-lg bg-muted p-3">
                                                    <div className="flex-1">
                                                        <p className="font-medium">{new Date(session.scheduled_at).toLocaleString('de')}</p>
                                                        <p className="text-sm text-muted-foreground">
                                                            {session.mentor_name} • {session.language}
                                                        </p>
                                                        <p className="mt-1 text-xs text-muted-foreground">
                                                            {session.available_spots} spots available
                                                        </p>
                                                    </div>
                                                    <Button onClick={() => handleSessionSignup(session.id)} variant="outline" size="sm">
                                                        Sign Up
                                                    </Button>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}
                        </CardContent>
                    </Card>
                )}

                {progress && (
                    <div className="space-y-4">
                        <h2 className="text-xl font-semibold">Training Modules</h2>

                        {progress.modules.map((module) => (
                            <Card key={module.id} className={module.status === 'completed' ? 'bg-muted/50' : ''}>
                                <CardHeader>
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            {module.status === 'completed' && <CheckCircle2 className="h-6 w-6 shrink-0 text-green-600" />}
                                            {module.status === 'waiting' && <Clock className="h-6 w-6 shrink-0 text-blue-600" />}
                                            {module.status === 'locked' && (
                                                <div className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-muted">
                                                    <span className="text-xs text-muted-foreground">{module.sequence_order}</span>
                                                </div>
                                            )}
                                            <div>
                                                <CardTitle>{module.name}</CardTitle>
                                                {module.completed_at && (
                                                    <p className="mt-1 text-sm text-muted-foreground">
                                                        Completed on {new Date(module.completed_at).toLocaleDateString('de')}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                        <Badge
                                            variant={
                                                module.status === 'completed' ? 'default' : module.status === 'waiting' ? 'secondary' : 'outline'
                                            }
                                            className="shrink-0"
                                        >
                                            {module.status === 'completed' ? 'Completed' : module.status === 'waiting' ? 'In Progress' : 'Locked'}
                                        </Badge>
                                    </div>
                                </CardHeader>

                                {module.status === 'waiting' && (
                                    <CardContent className="space-y-4">
                                        {/* Module 2: Show Moodle quiz completion (NO waiting list) */}
                                        {module.quiz_completion && (
                                            <div>
                                                <div className="mb-3 flex items-center justify-between">
                                                    <span className="text-sm font-medium">Moodle Course Progress</span>
                                                    <span className="text-sm text-muted-foreground">
                                                        {module.quiz_completion.completed} / {module.quiz_completion.total} completed
                                                    </span>
                                                </div>
                                                <Progress value={module.quiz_completion.percentage} className="mb-4 h-2" />

                                                <div className="space-y-2">
                                                    {module.quiz_completion.quizzes.map((quiz, index) => (
                                                        <div
                                                            key={quiz.id}
                                                            className="flex items-center justify-between rounded-lg border bg-card p-3"
                                                        >
                                                            <div className="flex items-center gap-3">
                                                                {quiz.completed ? (
                                                                    <CheckCircle2 className="h-5 w-5 shrink-0 text-green-600" />
                                                                ) : (
                                                                    <div className="h-5 w-5 shrink-0 rounded-full border-2 border-muted-foreground" />
                                                                )}
                                                                <span className="text-sm">{quiz.name || `Course ${index + 1}`}</span>
                                                            </div>
                                                            {quiz.url && !quiz.completed && (
                                                                <Button variant="outline" size="sm" onClick={() => window.open(quiz.url, '_blank')}>
                                                                    Open Course
                                                                    <ExternalLink className="ml-2 h-3 w-3" />
                                                                </Button>
                                                            )}
                                                            {quiz.completed && (
                                                                <Badge variant="default" className="bg-green-600">
                                                                    Completed
                                                                </Badge>
                                                            )}
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        )}

                                        {/* Modules 1, 3, 4: Show waiting list and sessions (NOT Module 2) */}
                                        {module.waiting_list && (
                                            <div className="flex justify-between space-y-3">
                                                <div className="text-sm">
                                                    <p className="-mt-2 text-muted-foreground">
                                                        Waiting list position: #{module.waiting_list.position} of {module.waiting_list.total_waiting}
                                                    </p>
                                                    <p className="text-muted-foreground">
                                                        Joined: {new Date(module.waiting_list.joined_at).toLocaleDateString('de')}
                                                    </p>
                                                </div>
                                                {!module.has_any_signup && (
                                                    <Button
                                                        onClick={() => handleLeaveWaitingList(module.id)}
                                                        variant="outline"
                                                        size="sm"
                                                        className="text-red-600 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-950"
                                                    >
                                                        Leave Waiting List
                                                    </Button>
                                                )}
                                            </div>
                                        )}

                                        {module.available_sessions && module.available_sessions.length > 0 && (
                                            <div>
                                                <h4 className="mb-2 text-sm font-medium">
                                                    {module.has_any_signup ? 'Your Session' : 'Available Sessions'}
                                                </h4>
                                                <div className="space-y-2">
                                                    {module.available_sessions.map((session) => (
                                                        <Card key={session.id} className="overflow-hidden py-0">
                                                            <CardContent className="p-4">
                                                                {/* Session Header */}
                                                                <div className="mb-3 flex items-start justify-between gap-4">
                                                                    <div className="flex-1 space-y-1">
                                                                        <div className="flex items-center gap-2">
                                                                            <Calendar className="h-4 w-4 text-muted-foreground" />
                                                                            <p className="font-semibold">
                                                                                {new Date(session.scheduled_at).toLocaleString('de', {
                                                                                    dateStyle: 'full',
                                                                                    timeStyle: 'short',
                                                                                })}
                                                                            </p>
                                                                        </div>
                                                                        <div className="flex items-center gap-2">
                                                                            <Users className="h-4 w-4 text-muted-foreground" />
                                                                            <p className="text-sm text-muted-foreground">
                                                                                Mentor: {session.mentor_name} • {session.language}
                                                                            </p>
                                                                        </div>
                                                                        <div className="text-sm text-muted-foreground">
                                                                            {session.signups_locked ? (
                                                                                <span>
                                                                                    Selected: {session.max_trainees - session.available_spots} /{' '}
                                                                                    {session.max_trainees}
                                                                                </span>
                                                                            ) : (
                                                                                <span>
                                                                                    Signups: {session.total_signups} • Spots:{' '}
                                                                                    {session.available_spots} / {session.max_trainees}
                                                                                </span>
                                                                            )}
                                                                        </div>
                                                                    </div>
                                                                    <div className="flex flex-col gap-2">
                                                                        {session.user_signed_up && (
                                                                            <>
                                                                                {session.user_selected ? (
                                                                                    <Badge variant="default" className="bg-green-600">
                                                                                        <CheckCircle2 className="mr-1 h-3 w-3" />
                                                                                        Selected
                                                                                    </Badge>
                                                                                ) : session.signups_locked ? (
                                                                                    <Badge variant="secondary">
                                                                                        <Clock className="mr-1 h-3 w-3" />
                                                                                        Selection Pending
                                                                                    </Badge>
                                                                                ) : (
                                                                                    <Badge variant="secondary">Signed Up</Badge>
                                                                                )}
                                                                                {session.user_waiting_position && (
                                                                                    <Badge variant="outline" className="text-xs">
                                                                                        Position #{session.user_waiting_position}
                                                                                    </Badge>
                                                                                )}
                                                                            </>
                                                                        )}
                                                                        {!session.user_signed_up && session.signups_locked && (
                                                                            <Badge variant="outline">Signups Closed</Badge>
                                                                        )}
                                                                        {!session.user_signed_up && !session.signups_locked && (
                                                                            <Badge variant="outline">{session.available_spots} spots left</Badge>
                                                                        )}
                                                                    </div>
                                                                </div>

                                                                {/* Session Notes */}
                                                                {session.notes && (
                                                                    <Alert className="mb-3">
                                                                        <AlertTitle className="text-sm">Session Notes</AlertTitle>
                                                                        <AlertDescription className="text-sm">{session.notes}</AlertDescription>
                                                                    </Alert>
                                                                )}

                                                                {/* Status Information */}
                                                                {session.signups_locked && (
                                                                    <Alert className="mb-3">
                                                                        <Clock className="h-4 w-4" />
                                                                        <AlertDescription className="text-sm">
                                                                            {session.user_signed_up ? (
                                                                                session.user_selected ? (
                                                                                    <span className="text-green-600 dark:text-green-400">
                                                                                        You have been selected for this session. Please be on time!
                                                                                    </span>
                                                                                ) : (
                                                                                    <span>
                                                                                        Selection in progress. Participants will be notified shortly.
                                                                                    </span>
                                                                                )
                                                                            ) : (
                                                                                <span>
                                                                                    Signups are closed. Selection based on waiting list position.
                                                                                </span>
                                                                            )}
                                                                        </AlertDescription>
                                                                    </Alert>
                                                                )}

                                                                {/* Action Buttons */}
                                                                <div className="flex gap-2">
                                                                    {/* User not signed up, signups open */}
                                                                    {!module.has_any_signup && !session.user_signed_up && !session.signups_locked && (
                                                                        <Button
                                                                            onClick={() => handleSessionSignup(session.id)}
                                                                            variant="default"
                                                                            size="sm"
                                                                            className="w-full"
                                                                        >
                                                                            Sign Up for This Session
                                                                        </Button>
                                                                    )}

                                                                    {/* User signed up, not selected, signups not locked yet */}
                                                                    {module.has_any_signup &&
                                                                        session.user_signed_up &&
                                                                        !session.user_selected &&
                                                                        !session.signups_locked && (
                                                                            <Button
                                                                                onClick={() => handleCancelSignup(session.id)}
                                                                                variant="outline"
                                                                                size="sm"
                                                                                className="w-full"
                                                                            >
                                                                                Cancel Signup
                                                                            </Button>
                                                                        )}

                                                                    {/* User selected - no action available */}
                                                                    {session.user_selected && (
                                                                        <div className="w-full rounded-md bg-green-50 p-2 text-center text-sm font-medium text-green-700 dark:bg-green-950 dark:text-green-300">
                                                                            You're confirmed for this session
                                                                        </div>
                                                                    )}
                                                                </div>
                                                            </CardContent>
                                                        </Card>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                    </CardContent>
                                )}
                            </Card>
                        ))}
                    </div>
                )}

                {progress?.canUpgrade && (
                    <Card className="border-green-500 bg-green-50 dark:bg-green-950">
                        <CardHeader>
                            <CardTitle className="text-green-700 dark:text-green-300">Ready for Rating Upgrade!</CardTitle>
                            <CardDescription className="text-green-600 dark:text-green-400">
                                Congratulations! You've completed all training modules and are eligible to request your S1 rating.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button onClick={() => router.post('/s1/request-upgrade')} size="lg" className="bg-green-600 hover:bg-green-700">
                                Request S1 Rating
                            </Button>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
