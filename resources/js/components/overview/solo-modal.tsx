import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Trainee } from '@/types/mentor';
import { router } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { Loader2, AlertCircle, Clock, Calendar, Info, Trash, CheckCircle, XCircle, AlertTriangle } from 'lucide-react';
import axios from 'axios';

interface SoloModalProps {
    trainee: Trainee | null;
    courseId: number | null;
    isOpen: boolean;
    onClose: () => void;
}

interface RequirementsStatus {
    trainee_id?: number;
    moodle: {
        completed: boolean;
        details?: Array<{ course_id: number; completed: boolean }>;
        error?: string;
    };
    core_theory: {
        status: 'passed' | 'assigned' | 'not_assigned' | 'not_required' | 'error';
        exam_id?: number;
        message?: string;
    };
    can_grant_solo: boolean;
}

export function SoloModal({ trainee, courseId, isOpen, onClose }: SoloModalProps) {
    const [mode, setMode] = useState<'none' | 'add' | 'extend' | 'remove'>('none');
    const [expiryDate, setExpiryDate] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [requirements, setRequirements] = useState<RequirementsStatus | null>(null);
    const [isLoadingRequirements, setIsLoadingRequirements] = useState(false);
    const [requirementsError, setRequirementsError] = useState<string | null>(null);
    const [isAssigningTest, setIsAssigningTest] = useState(false);

    useEffect(() => {
        if (isOpen && trainee) {
            const defaultDate = new Date();
            defaultDate.setDate(defaultDate.getDate() + 29);
            setExpiryDate(defaultDate.toISOString().split('T')[0]);

            setMode('none');
            setError(null);
            setRequirementsError(null);

            if (!trainee.soloStatus) {
                setRequirements(null);
            }
        }
    }, [isOpen, trainee]);

    const fetchRequirements = async () => {
        if (!trainee || !courseId) {
            console.error('Missing trainee or courseId', { trainee, courseId });
            return;
        }

        setIsLoadingRequirements(true);
        setRequirementsError(null);

        try {
            const response = await axios.post(route('overview.get-solo-requirements'), {
                trainee_id: trainee.id,
                course_id: courseId,
            });

            setRequirements(response.data);
            setRequirementsError(null);
        } catch (err: any) {
            console.error('Error fetching requirements:', err);
            const errorMessage = err.response?.data?.error || err.response?.data?.message || err.message || 'Failed to load requirements';
            setRequirementsError(errorMessage);

            setRequirements({
                moodle: { completed: false },
                core_theory: { status: 'error', message: errorMessage },
                can_grant_solo: false,
            });
        } finally {
            setIsLoadingRequirements(false);
        }
    };

    const handleAssignCoreTest = async () => {
        if (!trainee || !courseId) return;

        setIsAssigningTest(true);
        setError(null);

        try {
            const response = await axios.post(route('overview.assign-core-test'), {
                trainee_id: trainee.id,
                course_id: courseId,
            });

            if (response.data.success) {
                await fetchRequirements();
            } else {
                setError(response.data.message || 'Failed to assign core theory test');
            }
        } catch (err: any) {
            console.error('Error assigning core test:', err);
            setError(err.response?.data?.message || err.response?.data?.error || 'An error occurred while assigning the core theory test');
        } finally {
            setIsAssigningTest(false);
        }
    };

    const handleClose = () => {
        setMode('none');
        setError(null);
        setExpiryDate('');
        setRequirements(null);
        setRequirementsError(null);
        onClose();
    };

    const validateExpiryDate = (date: string): boolean => {
        const selectedDate = new Date(date);
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const maxDate = new Date();
        maxDate.setDate(maxDate.getDate() + 31);

        if (selectedDate < today) {
            setError('Expiry date cannot be in the past');
            return false;
        }

        if (selectedDate > maxDate) {
            setError('Solo endorsement cannot exceed 31 days from today');
            return false;
        }

        return true;
    };

    const handleAddSolo = () => {
        if (!trainee || !courseId || !expiryDate) return;

        if (!validateExpiryDate(expiryDate)) return;

        setIsSubmitting(true);
        setError(null);

        router.post(
            route('overview.add-solo'),
            {
                trainee_id: trainee.id,
                course_id: courseId,
                expiry_date: expiryDate,
            },
            {
                onSuccess: () => {
                    handleClose();
                },
                onError: (errors) => {
                    const errorMessage = Object.values(errors).flat()[0];
                    setError(typeof errorMessage === 'string' ? errorMessage : 'Failed to add solo');
                },
                onFinish: () => {
                    setIsSubmitting(false);
                },
            },
        );
    };

    const handleExtendSolo = () => {
        if (!trainee || !courseId || !expiryDate || !trainee.soloStatus) return;

        if (!validateExpiryDate(expiryDate)) return;

        setIsSubmitting(true);
        setError(null);

        router.post(
            route('overview.extend-solo'),
            {
                trainee_id: trainee.id,
                course_id: courseId,
                expiry_date: expiryDate,
            },
            {
                onSuccess: () => {
                    handleClose();
                },
                onError: (errors) => {
                    const errorMessage = Object.values(errors).flat()[0];
                    setError(typeof errorMessage === 'string' ? errorMessage : 'Failed to extend solo');
                },
                onFinish: () => {
                    setIsSubmitting(false);
                },
            },
        );
    };

    const handleRemoveSolo = () => {
        if (!trainee || !courseId) return;

        setIsSubmitting(true);
        setError(null);

        router.post(
            route('overview.remove-solo'),
            {
                trainee_id: trainee.id,
                course_id: courseId,
            },
            {
                onSuccess: () => {
                    handleClose();
                },
                onError: (errors) => {
                    const errorMessage = Object.values(errors).flat()[0];
                    setError(typeof errorMessage === 'string' ? errorMessage : 'Failed to remove solo');
                },
                onFinish: () => {
                    setIsSubmitting(false);
                },
            },
        );
    };

    const renderCoreTheoryStatus = () => {
        if (!requirements) return null;

        const { core_theory } = requirements;

        switch (core_theory.status) {
            case 'passed':
                return (
                    <div className="flex items-center gap-2 text-green-600 dark:text-green-400">
                        <CheckCircle className="h-4 w-4" />
                        <span className="text-sm font-medium">Core Theory Test Passed</span>
                    </div>
                );
            case 'assigned':
                return (
                    <div className="flex items-center gap-2 text-yellow-600 dark:text-yellow-400">
                        <AlertTriangle className="h-4 w-4" />
                        <span className="text-sm font-medium">Test Assigned - Awaiting Completion</span>
                    </div>
                );
            case 'not_assigned':
                return (
                    <div className="space-y-2">
                        <div className="flex items-center gap-2 text-red-600 dark:text-red-400">
                            <XCircle className="h-4 w-4" />
                            <span className="text-sm font-medium">Core Theory Test Not Assigned</span>
                        </div>
                        <Button onClick={handleAssignCoreTest} disabled={isAssigningTest} size="sm" variant="outline">
                            {isAssigningTest ? (
                                <>
                                    <Loader2 className="mr-2 h-3 w-3 animate-spin" />
                                    Assigning...
                                </>
                            ) : (
                                'Assign Core Theory Test'
                            )}
                        </Button>
                    </div>
                );
            case 'not_required':
                return (
                    <div className="flex items-center gap-2 text-muted-foreground">
                        <Info className="h-4 w-4" />
                        <span className="text-sm">Core Theory Test Not Required</span>
                    </div>
                );
            default:
                return (
                    <div className="flex items-center gap-2 text-red-600 dark:text-red-400">
                        <AlertCircle className="h-4 w-4" />
                        <span className="text-sm">{core_theory.message || 'Unable to verify status'}</span>
                    </div>
                );
        }
    };

    const renderMoodleStatus = () => {
        if (!requirements) return null;

        const { moodle } = requirements;

        if (moodle.error) {
            return (
                <div className="flex items-center gap-2 text-red-600 dark:text-red-400">
                    <AlertCircle className="h-4 w-4" />
                    <span className="text-sm">{moodle.error}</span>
                </div>
            );
        }

        if (moodle.completed) {
            return (
                <div className="flex items-center gap-2 text-green-600 dark:text-green-400">
                    <CheckCircle className="h-4 w-4" />
                    <span className="text-sm font-medium">All Moodle Courses Completed</span>
                </div>
            );
        }

        const incompleteCourses = moodle.details?.filter((d) => !d.completed).length || 0;
        const totalCourses = moodle.details?.length || 0;

        return (
            <div className="space-y-2">
                <div className="flex items-center gap-2 text-red-600 dark:text-red-400">
                    <XCircle className="h-4 w-4" />
                    <span className="text-sm font-medium">
                        Moodle Courses Incomplete ({totalCourses - incompleteCourses}/{totalCourses} completed)
                    </span>
                </div>
                {moodle.details && moodle.details.length > 0 && (
                    <div className="ml-6 space-y-1">
                        {moodle.details.map((detail, idx) => (
                            <div key={idx} className="flex items-center gap-2 text-xs">
                                {detail.completed ? <CheckCircle className="h-3 w-3 text-green-600" /> : <XCircle className="h-3 w-3 text-red-600" />}
                                <span>Course {detail.course_id}</span>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        );
    };

    const canProceed = requirements?.can_grant_solo || false;

    return (
        <Dialog open={isOpen} onOpenChange={handleClose}>
            <DialogContent className="max-h-[90vh] max-w-2xl overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Solo Endorsement - {trainee?.name}</DialogTitle>
                    <DialogDescription>
                        {trainee?.soloStatus ? 'Manage the solo endorsement for this trainee' : 'Grant a solo endorsement to this trainee'}
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-6 py-4">
                    {trainee?.soloStatus && (
                        <div className="rounded-lg border bg-muted/50 p-4">
                            <h3 className="mb-3 font-medium">Current Solo Status</h3>
                            <div className="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p className="text-muted-foreground">Remaining Days:</p>
                                    <p className="text-2xl font-semibold">{trainee.soloStatus.remaining}</p>
                                    <p className="text-xs text-muted-foreground">Until expiry</p>
                                </div>
                                <div>
                                    <p className="text-muted-foreground">Used Solo Days:</p>
                                    <p className="text-2xl font-semibold">{trainee.soloStatus.used}</p>
                                    <p className="text-xs text-muted-foreground">Days since creation</p>
                                </div>
                                <div className="col-span-2 border-t pt-3">
                                    <p className="text-muted-foreground">Expiry Date:</p>
                                    <p className="font-semibold">{new Date(trainee.soloStatus.expiry).toLocaleDateString('de')}</p>
                                </div>
                            </div>
                        </div>
                    )}

                    {!trainee?.soloStatus && mode === 'none' && (
                        <>
                            {isLoadingRequirements ? (
                                <div className="flex flex-col items-center justify-center space-y-4 py-8">
                                    <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                                    <p className="text-sm text-muted-foreground">Loading requirements...</p>
                                </div>
                            ) : requirementsError ? (
                                <div className="space-y-4">
                                    <Alert variant="destructive">
                                        <AlertCircle className="h-4 w-4" />
                                        <AlertDescription>
                                            <p className="font-medium">Failed to load requirements</p>
                                            <p className="mt-1 text-sm">{requirementsError}</p>
                                        </AlertDescription>
                                    </Alert>
                                    <Button onClick={fetchRequirements} variant="outline" className="w-full">
                                        Retry Loading Requirements
                                    </Button>
                                </div>
                            ) : requirements ? (
                                <div className="space-y-4">
                                    <Alert variant={canProceed ? 'default' : 'destructive'}>
                                        <Info className="h-4 w-4" />
                                        <AlertDescription>
                                            {canProceed
                                                ? 'All requirements met. You can grant a solo endorsement.'
                                                : 'Some requirements are not yet fulfilled. Please ensure all requirements are met before granting a solo endorsement.'}
                                        </AlertDescription>
                                    </Alert>

                                    <div className="space-y-4 rounded-lg border p-4">
                                        <h3 className="font-medium">Requirements Status</h3>

                                        <div className="space-y-3">
                                            <div>
                                                <p className="mb-2 text-sm font-medium">Core Theory Test:</p>
                                                {renderCoreTheoryStatus()}
                                            </div>

                                            <div>
                                                <p className="mb-2 text-sm font-medium">Moodle Courses:</p>
                                                {renderMoodleStatus()}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <Alert>
                                    <Info className="h-4 w-4" />
                                    <AlertDescription>Click "Add Solo Endorsement" to check requirements and proceed.</AlertDescription>
                                </Alert>
                            )}
                        </>
                    )}

                    {mode === 'none' && (
                        <div className="space-y-3">
                            {!trainee?.soloStatus ? (
                                <Button
                                    onClick={() => {
                                        if (!requirements && !requirementsError && !isLoadingRequirements) {
                                            fetchRequirements();
                                        } else if (canProceed || requirementsError) {
                                            setMode('add');
                                        }
                                    }}
                                    className="w-full"
                                    disabled={isLoadingRequirements || (requirements !== null && !canProceed && !requirementsError)}
                                >
                                    <Calendar className="mr-2 h-4 w-4" />
                                    {isLoadingRequirements ? 'Loading...' : 'Add Solo Endorsement'}
                                </Button>
                            ) : (
                                <>
                                    <Button onClick={() => setMode('extend')} className="w-full" variant="default">
                                        <Clock className="mr-2 h-4 w-4" />
                                        Extend Solo Endorsement
                                    </Button>
                                    <Button onClick={() => setMode('remove')} className="w-full" variant="destructive">
                                        <Trash className="mr-2 h-4 w-4" />
                                        Remove Solo Endorsement
                                    </Button>
                                </>
                            )}
                        </div>
                    )}

                    {(mode === 'add' || mode === 'extend') && (
                        <div className="space-y-4">
                            <Alert>
                                <AlertCircle className="h-4 w-4" />
                                <AlertDescription>
                                    Solo endorsements can be {mode === 'add' ? 'granted' : 'extended'} for a maximum of 31 days at a time.
                                </AlertDescription>
                            </Alert>

                            <div className="space-y-2">
                                <Label htmlFor="expiry-date">{mode === 'add' ? 'Expiry Date' : 'New Expiry Date'}</Label>
                                <Input
                                    id="expiry-date"
                                    type="date"
                                    value={expiryDate}
                                    onChange={(e) => {
                                        setExpiryDate(e.target.value);
                                        setError(null);
                                    }}
                                    min={new Date().toISOString().split('T')[0]}
                                    max={new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]}
                                />
                                <p className="text-xs text-muted-foreground">
                                    {mode === 'add'
                                        ? 'Select when this solo endorsement will expire (maximum 30 days from today)'
                                        : 'Select new expiry date to extend the solo endorsement (maximum 30 days from today)'}
                                </p>
                            </div>

                            {error && (
                                <Alert variant="destructive">
                                    <AlertCircle className="h-4 w-4" />
                                    <AlertDescription>{error}</AlertDescription>
                                </Alert>
                            )}

                            <div className="flex gap-2">
                                <Button variant="outline" onClick={() => setMode('none')} disabled={isSubmitting} className="flex-1">
                                    Cancel
                                </Button>
                                <Button
                                    onClick={mode === 'add' ? handleAddSolo : handleExtendSolo}
                                    disabled={isSubmitting || !expiryDate}
                                    className="flex-1"
                                >
                                    {isSubmitting ? (
                                        <>
                                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                            {mode === 'add' ? 'Adding...' : 'Extending...'}
                                        </>
                                    ) : (
                                        <>{mode === 'add' ? 'Add Solo' : 'Extend Solo'}</>
                                    )}
                                </Button>
                            </div>
                        </div>
                    )}

                    {mode === 'remove' && (
                        <div className="space-y-4">
                            <Alert variant="destructive">
                                <AlertCircle className="h-4 w-4" />
                                <AlertDescription>
                                    Are you sure you want to remove this solo endorsement? This action cannot be undone. The trainee will immediately
                                    lose their solo privileges.
                                </AlertDescription>
                            </Alert>

                            {error && (
                                <Alert variant="destructive">
                                    <AlertCircle className="h-4 w-4" />
                                    <AlertDescription>{error}</AlertDescription>
                                </Alert>
                            )}

                            <div className="flex gap-2">
                                <Button variant="outline" onClick={() => setMode('none')} disabled={isSubmitting} className="flex-1">
                                    Cancel
                                </Button>
                                <Button onClick={handleRemoveSolo} disabled={isSubmitting} variant="destructive" className="flex-1">
                                    {isSubmitting ? (
                                        <>
                                            <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                            Removing...
                                        </>
                                    ) : (
                                        'Confirm Removal'
                                    )}
                                </Button>
                            </div>
                        </div>
                    )}
                </div>

                {mode === 'none' && (
                    <DialogFooter>
                        <Button variant="outline" onClick={handleClose}>
                            Close
                        </Button>
                    </DialogFooter>
                )}
            </DialogContent>
        </Dialog>
    );
}