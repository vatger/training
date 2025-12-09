import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Mentor, Trainee } from '@/types/mentor';
import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

interface ClaimConfirmDialogProps {
    trainee: Trainee | null;
    courseId: number | null;
    isOpen: boolean;
    onClose: () => void;
}

export function ClaimConfirmDialog({ trainee, courseId, isOpen, onClose }: ClaimConfirmDialogProps) {
    const [isClaiming, setIsClaiming] = useState(false);

    const handleClaim = () => {
        if (!trainee || !courseId) return;

        setIsClaiming(true);
        router.post(
            route('overview.claim-trainee'),
            {
                trainee_id: trainee.id,
                course_id: courseId,
            },
            {
                preserveScroll: true,
                preserveState: true,
                only: ['courses'],
                onFinish: () => {
                    setIsClaiming(false);
                    onClose();
                },
            },
        );
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Claim Trainee</DialogTitle>
                    <DialogDescription>
                        {trainee?.claimedBy && trainee.claimedBy !== 'You' ? (
                            <>
                                <span className="font-medium">{trainee.name}</span> is currently claimed by{' '}
                                <span className="font-medium">{trainee.claimedBy}</span>.
                                <br />
                                <br />
                                By claiming this trainee, you will take over responsibility for their training.
                            </>
                        ) : (
                            <>
                                Are you sure you want to claim <span className="font-medium">{trainee?.name}</span>?
                                <br />
                                <br />
                                You will become the responsible mentor for this trainee.
                            </>
                        )}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" onClick={onClose} disabled={isClaiming}>
                        Cancel
                    </Button>
                    <Button onClick={handleClaim} disabled={isClaiming}>
                        {isClaiming ? 'Claiming...' : 'Claim Trainee'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

interface AssignDialogProps {
    trainee: Trainee | null;
    courseId: number | null;
    isOpen: boolean;
    onClose: () => void;
}

export function AssignDialog({ trainee, courseId, isOpen, onClose }: AssignDialogProps) {
    const [mentors, setMentors] = useState<Mentor[]>([]);
    const [selectedMentorId, setSelectedMentorId] = useState<string>('');
    const [isLoading, setIsLoading] = useState(false);
    const [isAssigning, setIsAssigning] = useState(false);

    useEffect(() => {
        if (isOpen && courseId) {
            setIsLoading(true);
            fetch(route('overview.get-course-mentors', courseId))
                .then((res) => res.json())
                .then((data) => {
                    setMentors(data);
                    setIsLoading(false);
                })
                .catch(() => {
                    setIsLoading(false);
                });
        }
    }, [isOpen, courseId]);

    const handleAssign = () => {
        if (!trainee || !courseId || !selectedMentorId) return;

        setIsAssigning(true);
        router.post(
            route('overview.assign-trainee'),
            {
                trainee_id: trainee.id,
                course_id: courseId,
                mentor_id: parseInt(selectedMentorId),
            },
            {
                preserveScroll: true,
                preserveState: true,
                only: ['courses'],
                onFinish: () => {
                    setIsAssigning(false);
                    setSelectedMentorId('');
                    onClose();
                },
            },
        );
    };

    const handleClose = () => {
        setSelectedMentorId('');
        onClose();
    };

    return (
        <Dialog open={isOpen} onOpenChange={handleClose}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Assign Trainee to Mentor</DialogTitle>
                    <DialogDescription>
                        Select a mentor to assign <span className="font-medium">{trainee?.name}</span> to.
                    </DialogDescription>
                </DialogHeader>
                <div className="py-4">
                    <Select value={selectedMentorId} onValueChange={setSelectedMentorId} disabled={isLoading}>
                        <SelectTrigger>
                            <SelectValue placeholder={isLoading ? 'Loading mentors...' : 'Select a mentor'} />
                        </SelectTrigger>
                        <SelectContent>
                            {mentors.map((mentor) => (
                                <SelectItem key={mentor.id} value={mentor.id.toString()}>
                                    {mentor.name} ({mentor.vatsim_id})
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
                <DialogFooter>
                    <Button variant="outline" onClick={handleClose} disabled={isAssigning}>
                        Cancel
                    </Button>
                    <Button onClick={handleAssign} disabled={!selectedMentorId || isAssigning}>
                        {isAssigning ? 'Assigning...' : 'Assign'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}