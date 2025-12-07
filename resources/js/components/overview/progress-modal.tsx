import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Trainee } from '@/types/mentor';
import { Link, router } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { Loader2, Plus, FileEdit, Calendar, Clock, CheckCircle2, XCircle, AlertCircle, Eye, FileText } from 'lucide-react';
import { cn } from '@/lib/utils';

interface TrainingLog {
    id: number;
    session_date: string;
    position: string;
    type: string;
    type_display: string;
    result: boolean;
    average_rating: number;
    session_duration: number | null;
    final_comment: string | null;
    next_step: string | null;
    mentor: {
        id: number;
        name: string;
    } | null;
    course: {
        id: number;
        name: string;
        position: string;
        type: string;
    } | null;
}

interface ProgressModalProps {
    trainee: Trainee | null;
    courseId: number | null;
    isOpen: boolean;
    onClose: () => void;
}

const DRAFT_STORAGE_KEY_PREFIX = 'training-log-draft-';

const formatGermanDate = (dateString: string): string => {
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}.${month}.${year}`;
};

export function ProgressModal({ trainee, courseId, isOpen, onClose }: ProgressModalProps) {
    const [logs, setLogs] = useState<TrainingLog[]>([]);
    const [isLoading, setIsLoading] = useState(false);
    const [hasDraft, setHasDraft] = useState(false);
    const [showDraftOptions, setShowDraftOptions] = useState(false);

    useEffect(() => {
        const fetchTraineeLogs = async () => {
            if (!trainee || !courseId) return;

            setIsLoading(true);
            try {
                const response = await fetch(route('api.training-logs.trainee', trainee.id));
                if (response.ok) {
                    const data = await response.json();
                    // FIX: Filter logs for this specific course only
                    const courseLogs = data.logs
                        .filter((log: TrainingLog) => log.course?.id === courseId)
                        .sort((a: TrainingLog, b: TrainingLog) => new Date(b.session_date).getTime() - new Date(a.session_date).getTime());
                    setLogs(courseLogs);
                }
            } catch (error) {
                console.error('Failed to fetch training logs:', error);
            } finally {
                setIsLoading(false);
            }
        };

        const checkForDraft = () => {
            if (!trainee || !courseId) return;

            const draftKey = `${DRAFT_STORAGE_KEY_PREFIX}${trainee.id}-${courseId}`;
            const draft = localStorage.getItem(draftKey);
            setHasDraft(!!draft);
        };

        if (isOpen && trainee && courseId) {
            fetchTraineeLogs();
            checkForDraft();
        }
    }, [isOpen, trainee, courseId]);

    const handleCreateNewLog = () => {
        if (!trainee || !courseId) return;

        if (hasDraft) {
            setShowDraftOptions(true);
        } else {
            navigateToCreateLog(false);
        }
    };

    const handleContinueDraft = () => {
        navigateToCreateLog(true);
    };

    const handleStartFresh = () => {
        if (!trainee || !courseId) return;

        const draftKey = `${DRAFT_STORAGE_KEY_PREFIX}${trainee.id}-${courseId}`;
        localStorage.removeItem(draftKey);
        setHasDraft(false);
        navigateToCreateLog(false);
    };

    const navigateToCreateLog = (continueDraft: boolean) => {
        if (!trainee || !courseId) return;

        router.visit(
            route('training-logs.create', {
                traineeId: trainee.id,
                courseId: courseId,
                continue: continueDraft ? '1' : undefined,
            }),
        );
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

    return (
        <>
            <Dialog open={isOpen && !showDraftOptions} onOpenChange={onClose}>
                <DialogContent className="flex max-h-[85vh] max-w-3xl flex-col">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 font-medium text-primary">
                                {trainee?.initials}
                            </div>
                            <div>
                                <div>{trainee?.name}</div>
                                <div className="text-sm font-normal text-muted-foreground">Training Progress</div>
                            </div>
                        </DialogTitle>
                    </DialogHeader>

                    <div className="flex flex-wrap gap-2 border-b pb-4">
                        <Button onClick={handleCreateNewLog} size="sm">
                            <Plus className="mr-2 h-4 w-4" />
                            New Training Log
                        </Button>
                        {hasDraft && (
                            <Button onClick={handleContinueDraft} size="sm" variant="outline">
                                <FileEdit className="mr-2 h-4 w-4" />
                                Continue Draft
                            </Button>
                        )}
                    </div>

                    <div className="flex-1 overflow-y-auto pr-2">
                        {isLoading ? (
                            <div className="flex items-center justify-center py-12">
                                <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                            </div>
                        ) : logs.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-12 text-center">
                                <FileText className="mb-4 h-12 w-12 text-muted-foreground" />
                                <h3 className="mb-2 text-lg font-medium">No training logs yet</h3>
                                <p className="mb-4 text-sm text-muted-foreground">Get started by creating a new training log.</p>
                            </div>
                        ) : (
                            <div className="relative space-y-6 pl-8 before:absolute before:top-0 before:bottom-0 before:left-4 before:w-0.5 before:bg-border">
                                {logs.map((log) => (
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
                                                        <Badge variant="outline" className={getSessionTypeColor(log.type)}>
                                                            {log.type_display}
                                                        </Badge>
                                                        <Badge variant={log.result ? 'default' : 'destructive'} className="flex items-center gap-1">
                                                            {log.result ? (
                                                                <>
                                                                    <CheckCircle2 className="h-3 w-3" />
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
                                                    <h4 className="font-monospace font-semibold">{log.position}</h4>
                                                    <div className="mt-2 flex items-center gap-4 text-sm text-muted-foreground">
                                                        <span className="flex items-center gap-1">
                                                            <Calendar className="h-3 w-3" />
                                                            {formatGermanDate(log.session_date)}
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
                                                    <p className="mb-1 text-sm font-medium text-muted-foreground">Next Step:</p>
                                                    <p className="text-sm">{log.next_step}</p>
                                                </div>
                                            )}

                                            <div className="mt-3 text-xs text-muted-foreground">Mentor: {log.mentor?.name ?? 'Unknown'}</div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                    <DialogFooter className="border-t pt-4">
                        <Button variant="outline" onClick={onClose}>
                            Close
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={showDraftOptions} onOpenChange={() => setShowDraftOptions(false)}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <AlertCircle className="h-5 w-5 text-yellow-500" />
                            Draft Found
                        </DialogTitle>
                        <DialogDescription>You have an unsaved draft for {trainee?.name}. What would you like to do?</DialogDescription>
                    </DialogHeader>

                    <Alert>
                        <AlertDescription>
                            Your previous work has been automatically saved. You can continue where you left off or start fresh.
                        </AlertDescription>
                    </Alert>

                    <div className="flex flex-col gap-3">
                        <Button onClick={handleContinueDraft} className="w-full justify-start">
                            <FileEdit className="mr-2 h-4 w-4" />
                            Continue Draft
                        </Button>
                        <Button onClick={handleStartFresh} variant="outline" className="w-full justify-start">
                            <Plus className="mr-2 h-4 w-4" />
                            Start Fresh
                        </Button>
                    </div>

                    <DialogFooter>
                        <Button variant="ghost" onClick={() => setShowDraftOptions(false)}>
                            Cancel
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}