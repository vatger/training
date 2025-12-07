import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { CheckCircle, XCircle, Calendar, Clock, Eye, FileText } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { Course } from '@/pages/trainee-dashboard';

interface CourseLogsModalProps {
    course: Course | null;
    isOpen: boolean;
    onClose: () => void;
}

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

export function CourseLogsModal({ course, isOpen, onClose }: CourseLogsModalProps) {
    if (!course) return null;

    const logs = course.all_logs || [];

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="flex max-h-[85vh] max-w-3xl flex-col">
                <DialogHeader>
                    <DialogTitle>{course.trainee_display_name}</DialogTitle>
                    <DialogDescription>Training session history</DialogDescription>
                </DialogHeader>

                <div className="flex-1 overflow-y-auto pr-2">
                    {logs.length === 0 ? (
                        <div className="flex flex-col items-center justify-center py-12 text-center">
                            <FileText className="mb-4 h-12 w-12 text-muted-foreground" />
                            <h3 className="mb-2 text-lg font-medium">No training logs yet</h3>
                            <p className="text-sm text-muted-foreground">Training logs will appear here once your mentor creates them.</p>
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
                                                    {log.average_rating !== null && log.average_rating > 0 && (
                                                        <Badge variant="secondary">Avg: {log.average_rating.toFixed(1)}/4</Badge>
                                                    )}
                                                </div>
                                                <h4 className="font-monospace font-semibold">{log.position}</h4>
                                                <div className="mt-2 flex items-center gap-4 text-sm text-muted-foreground">
                                                    <span className="flex items-center gap-1">
                                                        <Calendar className="h-3 w-3" />
                                                        {new Date(log.session_date).toLocaleDateString('de')}
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
                                            <div className="mt-3 rounded-md bg-blue-50 p-3 dark:bg-blue-900/20">
                                                <p className="mb-1 text-sm font-medium text-blue-900 dark:text-blue-300">Next Step:</p>
                                                <p className="text-sm text-blue-800 dark:text-blue-200">{log.next_step}</p>
                                            </div>
                                        )}

                                        <div className="mt-3 text-xs text-muted-foreground">Mentor: {log.mentor_name ?? 'Unknown'}</div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div className="border-t pt-4">
                    <Button variant="outline" onClick={onClose} className="w-full">
                        Close
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}