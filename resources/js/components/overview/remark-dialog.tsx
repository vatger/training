import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Trainee } from '@/types/mentor';
import { router } from '@inertiajs/react';
import { useState, useEffect } from 'react';

interface RemarkDialogProps {
    trainee: Trainee | null;
    courseId: number | null;
    isOpen: boolean;
    onClose: () => void;
}

export function RemarkDialog({ trainee, courseId, isOpen, onClose }: RemarkDialogProps) {
    const [remarkText, setRemarkText] = useState('');
    const [isSaving, setIsSaving] = useState(false);

    // Update remarkText when trainee changes
    useEffect(() => {
        if (trainee) {
            setRemarkText(trainee.remark?.text || '');
        }
    }, [trainee]);

    const handleSave = () => {
        if (!trainee || !courseId) return;

        setIsSaving(true);
        router.post(
            route('overview.update-remark'),
            {
                trainee_id: trainee.id,
                course_id: courseId,
                remark: remarkText,
            },
            {
                onFinish: () => {
                    setIsSaving(false);
                    onClose();
                },
            },
        );
    };

    const handleClose = () => {
        setRemarkText(trainee?.remark?.text || '');
        onClose();
    };

    return (
        <Dialog open={isOpen} onOpenChange={handleClose}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Update Remark - {trainee?.name}</DialogTitle>
                    <DialogDescription>Add notes about this trainee's availability, performance, or other relevant information.</DialogDescription>
                </DialogHeader>
                <Textarea
                    placeholder="Enter remarks about this trainee..."
                    value={remarkText}
                    onChange={(e) => setRemarkText(e.target.value)}
                    rows={4}
                    maxLength={1000}
                />
                <div className="text-right text-sm text-muted-foreground">{remarkText.length}/1000</div>
                <DialogFooter>
                    <Button variant="outline" onClick={handleClose} disabled={isSaving}>
                        Cancel
                    </Button>
                    <Button onClick={handleSave} disabled={isSaving}>
                        {isSaving ? 'Saving...' : 'Save'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}