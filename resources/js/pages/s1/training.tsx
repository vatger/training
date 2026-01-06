import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { CheckCircle2, Clock, AlertCircle, ExternalLink, Users, Calendar } from 'lucide-react';
import { BreadcrumbItem } from '@/types';

interface Module {
    id: number;
    name: string;
    sequence_order: number;
}

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
    user_signed_up: boolean;
    user_selected: boolean;
}

interface QuizCompletion {
    completed: number;
    total: number;
    percentage: number;
    quizzes: Array<{ id: number; completed: boolean }>;
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
    modules: Module[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'S1 Training',
        href: route('s1.training'),
    },
];

export default function S1Training({ isVatsimUser, currentStep, progress, modules }: Props) {
    const joinWaitingListForm = useForm({
        confirm_requirements: true,
    });

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
                router.post(`/s1/waiting-list/${currentStep.action_data.module_id}/leave`, {}, {
                    preserveScroll: true,
                });
            } else if (currentStep.type === 'signup_available' && currentStep.action_data?.session_id) {
                router.post(`/s1/session/${currentStep.action_data.session_id}/signup`, {}, {
                    preserveScroll: true,
                });
            } else if (currentStep.type === 'request_upgrade') {
                router.post('/s1/request-upgrade', {}, {
                    preserveScroll: true,
                });
            }
        }
    };

    const handleSessionSignup = (sessionId: number) => {
        router.post(`/s1/session/${sessionId}/signup`, {}, {
            preserveScroll: true,
        });
    };

    const handleConfirmWaitingList = (waitingListId: number) => {
        router.post(`/s1/waiting-list/${waitingListId}/confirm`, {}, {
            preserveScroll: true,
        });
    };

    const handleCancelSignup = (sessionId: number) => {
        router.post(`/s1/session/${sessionId}/cancel`, {}, {
            preserveScroll: true,
        });
    };

    if (!isVatsimUser) {
        return (
            <AppLayout>
                <Head title="S1 Training" />
                <div className="py-12">
                    <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
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
            
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                    {progress && (
                        <Card>
                            <CardHeader>
                                <div>
                                    <h1 className="text-3xl font-bold">S1 Training Journey</h1>
                                    <p className="text-muted-foreground mt-2">
                                        Complete the modules step-by-step to earn your S1 rating
                                    </p>
                                </div>
                                <CardDescription>
                                    {progress.modules.filter(m => m.status === 'completed').length} of {progress.modules.length} modules completed
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Progress value={progress.overallPercentage} className="h-3" />
                                <p className="text-sm text-muted-foreground mt-2">{progress.overallPercentage}% complete</p>
                            </CardContent>
                        </Card>
                    )}

                    {currentStep && (
                        <Card className="border-primary shadow-lg">
                            <CardHeader>
                                <div className="flex items-start justify-between">
                                    <div className="flex-1">
                                        <CardTitle className="text-2xl">{currentStep.title}</CardTitle>
                                        <CardDescription className="mt-2 text-base">
                                            {currentStep.description}
                                        </CardDescription>
                                    </div>
                                    <Badge variant="default" className="ml-4 shrink-0">Current Step</Badge>
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {currentStep.type === 'complete_quizzes' && currentStep.progress !== undefined && (
                                    <div>
                                        <Progress value={currentStep.progress} className="h-2" />
                                        <p className="text-sm text-muted-foreground mt-1">{currentStep.progress}% complete</p>
                                    </div>
                                )}

                                {currentStep.type === 'signup_available' && currentStep.session && (
                                    <Alert>
                                        <Calendar className="h-4 w-4" />
                                        <AlertTitle>Session Details</AlertTitle>
                                        <AlertDescription>
                                            <div className="space-y-1 mt-2">
                                                <p><strong>Date:</strong> {new Date(currentStep.session.scheduled_at).toLocaleString()}</p>
                                                <p><strong>Mentor:</strong> {currentStep.session.mentor_name}</p>
                                                <p><strong>Language:</strong> {currentStep.session.language}</p>
                                                <p><strong>Available Spots:</strong> {currentStep.session.available_spots} / {currentStep.session.max_trainees}</p>
                                                {currentStep.waiting_position && (
                                                    <p className="text-sm text-muted-foreground mt-2">
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
                                            <p className="text-green-700 dark:text-green-300 mt-2">
                                                <strong>Date:</strong> {new Date(currentStep.session.scheduled_at).toLocaleString()}
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
                                            <p className="mt-2">
                                                Session: {new Date(currentStep.session.scheduled_at).toLocaleString()}
                                            </p>
                                            <p className="text-sm text-muted-foreground mt-1">
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
                                            <p className="text-sm text-muted-foreground mt-1">
                                                You'll be notified when training sessions become available. Sessions select participants from the top of the waiting list.
                                            </p>
                                            {currentStep.needs_confirmation && currentStep.confirmation_data && (
                                                <div className="mt-3">
                                                    <p className="text-yellow-600 dark:text-yellow-400 font-medium mb-2">
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

                                {/* Show all available sessions for signup_available */}
                                {currentStep.type === 'signup_available' && currentStep.module?.available_sessions && currentStep.module.available_sessions.length > 1 && (
                                    <div className="mt-4">
                                        <h4 className="text-sm font-medium mb-3">Or choose from other available sessions:</h4>
                                        <div className="space-y-2">
                                            {currentStep.module.available_sessions.slice(1).map((session) => (
                                                <div key={session.id} className="flex items-center justify-between p-3 bg-muted rounded-lg">
                                                    <div className="flex-1">
                                                        <p className="font-medium">{new Date(session.scheduled_at).toLocaleString()}</p>
                                                        <p className="text-sm text-muted-foreground">{session.mentor_name} • {session.language}</p>
                                                        <p className="text-xs text-muted-foreground mt-1">{session.available_spots} spots available</p>
                                                    </div>
                                                    <Button
                                                        onClick={() => handleSessionSignup(session.id)}
                                                        variant="outline"
                                                        size="sm"
                                                    >
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
                                <Card 
                                    key={module.id}
                                    className={module.status === 'completed' ? 'bg-muted/50' : ''}
                                >
                                    <CardHeader>
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-3">
                                                {module.status === 'completed' && (
                                                    <CheckCircle2 className="h-6 w-6 text-green-600 shrink-0" />
                                                )}
                                                {module.status === 'waiting' && (
                                                    <Clock className="h-6 w-6 text-blue-600 shrink-0" />
                                                )}
                                                {module.status === 'locked' && (
                                                    <div className="h-6 w-6 rounded-full bg-muted flex items-center justify-center shrink-0">
                                                        <span className="text-xs text-muted-foreground">{module.sequence_order}</span>
                                                    </div>
                                                )}
                                                <div>
                                                    <CardTitle>{module.name}</CardTitle>
                                                    {module.completed_at && (
                                                        <p className="text-sm text-muted-foreground mt-1">
                                                            Completed on {new Date(module.completed_at).toLocaleDateString()}
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                            <Badge 
                                                variant={
                                                    module.status === 'completed' ? 'default' :
                                                    module.status === 'waiting' ? 'secondary' :
                                                    'outline'
                                                }
                                                className="shrink-0"
                                            >
                                                {module.status === 'completed' ? 'Completed' :
                                                 module.status === 'waiting' ? 'In Progress' :
                                                 'Locked'}
                                            </Badge>
                                        </div>
                                    </CardHeader>
                                    
                                    {module.status === 'waiting' && (
                                        <CardContent className="space-y-4">
                                            {module.quiz_completion && (
                                                <div>
                                                    <div className="flex items-center justify-between mb-2">
                                                        <span className="text-sm font-medium">Quiz Progress</span>
                                                        <span className="text-sm text-muted-foreground">
                                                            {module.quiz_completion.completed} / {module.quiz_completion.total}
                                                        </span>
                                                    </div>
                                                    <Progress value={module.quiz_completion.percentage} className="h-2" />
                                                </div>
                                            )}

                                            {module.waiting_list && (
                                                <div className="space-y-3">
                                                    <div className="text-sm">
                                                        <p className="text-muted-foreground">
                                                            Waiting list position: #{module.waiting_list.position} of {module.waiting_list.total_waiting}
                                                        </p>
                                                        <p className="text-muted-foreground">
                                                            Joined: {new Date(module.waiting_list.joined_at).toLocaleDateString()}
                                                        </p>
                                                    </div>
                                                    {!module.has_any_signup && (
                                                        <Button
                                                            onClick={() => router.post(`/s1/waiting-list/${module.id}/leave`, {}, {
                                                                preserveScroll: true,
                                                            })}
                                                            variant="outline"
                                                            size="sm"
                                                            className="w-full text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950"
                                                        >
                                                            Leave Waiting List
                                                        </Button>
                                                    )}
                                                </div>
                                            )}

                                            {module.available_sessions && module.available_sessions.length > 0 && (
                                                <div>
                                                    <h4 className="text-sm font-medium mb-2">
                                                        {module.has_any_signup ? 'Your Session' : 'Available Sessions'}
                                                    </h4>
                                                    <div className="space-y-2">
                                                        {module.available_sessions.map((session) => (
                                                            <div key={session.id} className="text-sm p-3 bg-muted rounded-lg">
                                                                <div className="flex items-center justify-between gap-4 mb-2">
                                                                    <div className="min-w-0 flex-1">
                                                                        <p className="font-medium truncate">{new Date(session.scheduled_at).toLocaleString()}</p>
                                                                        <p className="text-muted-foreground">{session.mentor_name} • {session.language}</p>
                                                                    </div>
                                                                    <div className="shrink-0">
                                                                        {session.user_signed_up ? (
                                                                            <Badge variant={session.user_selected ? 'default' : 'secondary'}>
                                                                                {session.user_selected ? 'Selected' : 'Signed Up'}
                                                                            </Badge>
                                                                        ) : (
                                                                            <Badge variant="outline">
                                                                                {session.available_spots} spots
                                                                            </Badge>
                                                                        )}
                                                                    </div>
                                                                </div>
                                                                {/* Only show action buttons if user doesn't have any signup */}
                                                                {!module.has_any_signup && (
                                                                    <>
                                                                        {!session.user_signed_up && (
                                                                            <Button
                                                                                onClick={() => handleSessionSignup(session.id)}
                                                                                variant="outline"
                                                                                size="sm"
                                                                                className="w-full"
                                                                            >
                                                                                Sign Up for This Session
                                                                            </Button>
                                                                        )}
                                                                    </>
                                                                )}
                                                                {/* Show cancel button if user is signed up but not selected */}
                                                                {module.has_any_signup && session.user_signed_up && !session.user_selected && (
                                                                    <Button
                                                                        onClick={() => handleCancelSignup(session.id)}
                                                                        variant="outline"
                                                                        size="sm"
                                                                        className="w-full"
                                                                    >
                                                                        Cancel Signup
                                                                    </Button>
                                                                )}
                                                            </div>
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
                                <Button 
                                    onClick={() => router.post('/s1/request-upgrade')}
                                    size="lg"
                                    className="bg-green-600 hover:bg-green-700"
                                >
                                    Request S1 Rating
                                </Button>
                            </CardContent>
                        </Card>
                    )}
            </div>
        </AppLayout>
    );
}