import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Course } from '@/pages/training/courses';
import { router } from '@inertiajs/react';
import { Clock, AlertCircle, X, Loader2, CheckCircle } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

interface WaitingListButtonProps {
    course: Course;
    onCourseUpdate?: (courseId: number, updates: Partial<Course>) => void;
    variant?: 'default' | 'compact';
    className?: string;
    size?: 'sm' | 'default' | 'lg';
    userHasActiveRtgCourse?: boolean;
}

export default function WaitingListButton({
    course,
    onCourseUpdate,
    variant = 'default',
    className = '',
    size = 'sm',
    userHasActiveRtgCourse = false,
}: WaitingListButtonProps) {
    const [isLoading, setIsLoading] = useState(false);
    const [loadingAction, setLoadingAction] = useState<'joining' | 'leaving' | null>(null);
    const [showLeaveConfirmation, setShowLeaveConfirmation] = useState(false);

    const handleJoinWaitingList = async () => {
        if (isLoading || !course.can_join) return;

        setIsLoading(true);
        setLoadingAction('joining');

        const optimisticUpdates: Partial<Course> = {
            is_on_waiting_list: true,
            waiting_list_position: undefined,
            waiting_list_activity: undefined,
        };

        onCourseUpdate?.(course.id, optimisticUpdates);

        try {
            await new Promise<void>((resolve, reject) => {
                router.post(
                    `/courses/${course.id}/waiting-list`,
                    {},
                    {
                        preserveState: true,
                        preserveScroll: true,
                        onSuccess: (page) => {
                            const flashData = page.props.flash || {};
                            const response = flashData.flash || flashData;

                            const position = response?.position;
                            const activity = response?.activity;

                            const serverUpdates: Partial<Course> = {
                                is_on_waiting_list: true,
                                waiting_list_position: position,
                                waiting_list_activity: activity,
                            };

                            onCourseUpdate?.(course.id, serverUpdates);

                            toast.success(`Successfully joined waiting list!`, {
                                description: position ? `Your position: #${position}` : undefined,
                            });

                            resolve();
                        },
                        onError: (errors) => {
                            onCourseUpdate?.(course.id, {
                                is_on_waiting_list: false,
                                waiting_list_position: undefined,
                                waiting_list_activity: undefined,
                            });

                            const errorMessage = Object.values(errors).flat()[0] || 'An error occurred';
                            toast.error(typeof errorMessage === 'string' ? errorMessage : 'Failed to join waiting list');

                            reject(new Error('Inertia request failed'));
                        },
                    },
                );
            });
        } catch (error) {
            console.error('Error joining waiting list:', error);
            toast.error('Connection error');

            onCourseUpdate?.(course.id, {
                is_on_waiting_list: false,
                waiting_list_position: undefined,
                waiting_list_activity: undefined,
            });
        } finally {
            setIsLoading(false);
            setLoadingAction(null);
        }
    };

    const handleLeaveWaitingList = async () => {
        if (isLoading || !course.is_on_waiting_list) return;

        setShowLeaveConfirmation(false);
        setIsLoading(true);
        setLoadingAction('leaving');

        const originalPosition = course.waiting_list_position;
        const originalActivity = course.waiting_list_activity;

        const optimisticUpdates: Partial<Course> = {
            is_on_waiting_list: false,
            waiting_list_position: undefined,
            waiting_list_activity: undefined,
        };

        onCourseUpdate?.(course.id, optimisticUpdates);

        try {
            await new Promise<void>((resolve, reject) => {
                router.post(
                    `/courses/${course.id}/waiting-list`,
                    {},
                    {
                        preserveState: true,
                        preserveScroll: true,
                        onSuccess: (page) => {
                            const flashData = page.props.flash || {};
                            const response = flashData.flash || flashData;

                            if (response.success !== false) {
                                toast.success('Successfully left waiting list!');
                            } else {
                                onCourseUpdate?.(course.id, {
                                    is_on_waiting_list: true,
                                    waiting_list_position: originalPosition,
                                    waiting_list_activity: originalActivity,
                                });

                                toast.error(response.message || 'Failed to leave waiting list');
                            }
                            resolve();
                        },
                        onError: (errors) => {
                            onCourseUpdate?.(course.id, {
                                is_on_waiting_list: true,
                                waiting_list_position: originalPosition,
                                waiting_list_activity: originalActivity,
                            });

                            const errorMessage = Object.values(errors).flat()[0] || 'An error occurred';
                            toast.error(typeof errorMessage === 'string' ? errorMessage : 'Failed to leave waiting list');

                            reject(new Error('Inertia request failed'));
                        },
                    },
                );
            });
        } catch (error) {
            console.error('Error leaving waiting list:', error);
            toast.error('Connection error');
        } finally {
            setIsLoading(false);
            setLoadingAction(null);
        }
    };

    const handleButtonClick = () => {
        if (course.type === 'RTG' && userHasActiveRtgCourse && !course.is_on_waiting_list) {
            toast.error('You can only join one rating course at a time');
            return;
        }

        if (course.is_on_waiting_list) {
            setShowLeaveConfirmation(true);
        } else if (course.can_join) {
            handleJoinWaitingList();
        }
    };

    const getButtonContent = () => {
        if (isLoading) {
            return (
                <>
                    <Loader2 className="h-4 w-4 animate-spin" />
                    {variant === 'compact' ? '' : loadingAction === 'leaving' ? 'Leaving...' : 'Joining...'}
                </>
            );
        }

        if (course.is_on_waiting_list) {
            return (
                <>
                    {variant === 'compact' ? <X className="h-4 w-4" /> : <X className="h-4 w-4" />}
                    {variant === 'compact' ? '' : 'Leave Queue'}
                </>
            );
        }

        return (
            <>
                {variant === 'compact' ? <CheckCircle className="h-4 w-4" /> : <Clock className="h-4 w-4" />}
                {variant === 'compact' ? '' : 'Join Queue'}
            </>
        );
    };

    const isDisabledDueToRtgRestriction = course.type === 'RTG' && userHasActiveRtgCourse && !course.is_on_waiting_list;
    const isButtonDisabled = isLoading || (!course.can_join && !course.is_on_waiting_list) || isDisabledDueToRtgRestriction;

    const getTooltipError = () => {
        if (isDisabledDueToRtgRestriction) {
            return 'You can only join one rating course at a time';
        }
        return course.join_error || 'Cannot join this course at the moment';
    };

    const button = (
        <Button
            onClick={handleButtonClick}
            disabled={isButtonDisabled}
            variant={course.is_on_waiting_list ? 'destructive' : 'default'}
            className={className}
            size={size}
        >
            {getButtonContent()}
        </Button>
    );

    if (isButtonDisabled && !course.is_on_waiting_list) {
        return (
            <>
                <TooltipProvider>
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <div className={variant === 'compact' ? '' : 'w-full'}>{button}</div>
                        </TooltipTrigger>
                        <TooltipContent side="top" className="max-w-xs">
                            <div className="flex items-center gap-2">
                                <AlertCircle className="h-4 w-4" />
                                <span>{getTooltipError()}</span>
                            </div>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>

                <Dialog open={showLeaveConfirmation} onOpenChange={setShowLeaveConfirmation}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Leave Waiting List</DialogTitle>
                            <DialogDescription>
                                Are you sure you want to leave the waiting list for <strong>{course.trainee_display_name || course.name}</strong>?
                                {course.waiting_list_position && (
                                    <span className="mt-2 block text-sm">
                                        You are currently at position #{course.waiting_list_position} and will lose your place.
                                    </span>
                                )}
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setShowLeaveConfirmation(false)}>
                                Cancel
                            </Button>
                            <Button variant="destructive" onClick={handleLeaveWaitingList}>
                                Leave Queue
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </>
        );
    }

    return (
        <>
            <div className={variant === 'compact' ? '' : 'w-full'}>{button}</div>

            <Dialog open={showLeaveConfirmation} onOpenChange={setShowLeaveConfirmation}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Leave Waiting List</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to leave the waiting list for <strong>{course.trainee_display_name || course.name}</strong>?
                            {course.waiting_list_position && (
                                <span className="mt-2 block text-sm">
                                    You are currently at position #{course.waiting_list_position} and will lose your place.
                                </span>
                            )}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowLeaveConfirmation(false)}>
                            Cancel
                        </Button>
                        <Button variant="destructive" onClick={handleLeaveWaitingList}>
                            Leave Queue
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}