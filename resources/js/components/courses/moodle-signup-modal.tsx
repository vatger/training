import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { AlertCircle, ExternalLink } from 'lucide-react';
import { router } from '@inertiajs/react';

interface MoodleSignupModalProps {
    isOpen: boolean;
}

export function MoodleSignupModal({ isOpen }: MoodleSignupModalProps) {
    const handleGoToMoodle = () => {
        window.open('https://moodle.vatsim-germany.org/', '_blank');
    };

    const handleBackToDashboard = () => {
        router.visit(route('dashboard'));
    };

    return (
        <AlertDialog open={isOpen}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <div className="flex items-center gap-2">
                        <AlertCircle className="h-5 w-5 text-yellow-500" />
                        <AlertDialogTitle>Moodle Account Required</AlertDialogTitle>
                    </div>
                    <AlertDialogDescription className="space-y-4 pt-4">
                        <p>Before you can register for courses, you need to sign up on the VATSIM Germany Moodle platform.</p>
                        <p className="text-sm">
                            Many courses require completion of Moodle training modules. Please create your account first to ensure a smooth training
                            experience.
                        </p>
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <Button variant="outline" onClick={handleBackToDashboard}>
                        Back to Dashboard
                    </Button>
                    <Button onClick={handleGoToMoodle}>
                        <ExternalLink className="mr-2 h-4 w-4" />
                        Go to Moodle
                    </Button>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}