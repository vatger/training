import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { MentorCourse } from '@/types/mentor';
import { Link, router } from '@inertiajs/react';
import { Calendar, Loader2, Search, UserPlus, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

interface PastTrainee {
    id: number;
    vatsim_id: number;
    name: string;
    completed_at: string;
}

interface PastTraineesModalProps {
    course: MentorCourse | null;
    isOpen: boolean;
    onClose: () => void;
}

export function PastTraineesModal({ course, isOpen, onClose }: PastTraineesModalProps) {
    const [trainees, setTrainees] = useState<PastTrainee[]>([]);
    const [isLoading, setIsLoading] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (isOpen && course) {
            setIsLoading(true);
            setError(null);

            // Fetch past trainees from the API
            fetch(route('overview.past-trainees', { course: course.id }))
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Failed to fetch past trainees');
                    }
                    return response.json();
                })
                .then((data) => {
                    if (data.success) {
                        setTrainees(data.trainees);
                    } else {
                        setError(data.error || 'Failed to fetch past trainees');
                    }
                })
                .catch((err) => {
                    console.error('Error fetching past trainees:', err);
                    setError('Failed to load past trainees. Please try again.');
                })
                .finally(() => {
                    setIsLoading(false);
                });
        }
    }, [isOpen, course]);

    const filteredTrainees = useMemo(() => {
        return trainees.filter((trainee) => {
            const matchesSearch =
                !searchTerm || trainee.name.toLowerCase().includes(searchTerm.toLowerCase()) || trainee.vatsim_id.toString().includes(searchTerm);

            return matchesSearch;
        });
    }, [trainees, searchTerm]);

    const getInitials = (name: string): string => {
        const parts = name.split(' ');
        if (parts.length >= 2) {
            return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
        }
        return name.substring(0, 2).toUpperCase();
    };

    const handleReactivate = (traineeId: number) => {
        if (!course) return;

        router.post(
            route('overview.reactivate-trainee'),
            {
                trainee_id: traineeId,
                course_id: course.id,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    // Refresh the past trainees list
                    fetch(route('overview.past-trainees', { course: course.id }))
                        .then((response) => response.json())
                        .then((data) => {
                            if (data.success) {
                                setTrainees(data.trainees);
                            }
                        });
                },
            },
        );
    };

    const handleClose = () => {
        setSearchTerm('');
        setError(null);
        onClose();
    };

    return (
        <Dialog open={isOpen} onOpenChange={handleClose}>
            <DialogContent className="max-h-[85vh] max-w-4xl overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Past Trainees - {course?.name}</DialogTitle>
                    <DialogDescription>View and reactivate trainees who have completed this course</DialogDescription>
                </DialogHeader>

                {/* Search */}
                <div className="flex gap-3 py-4">
                    <div className="relative flex-1">
                        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            placeholder="Search by name or VATSIM ID..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="pl-10"
                        />
                        {searchTerm && (
                            <Button
                                variant="ghost"
                                size="sm"
                                className="absolute top-1/2 right-1 h-7 w-7 -translate-y-1/2 p-0"
                                onClick={() => setSearchTerm('')}
                            >
                                <X className="h-4 w-4" />
                            </Button>
                        )}
                    </div>
                </div>

                {/* Trainees List */}
                <div>
                    {isLoading ? (
                        <div className="flex items-center justify-center py-12">
                            <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
                        </div>
                    ) : error ? (
                        <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-center dark:border-red-800 dark:bg-red-950">
                            <X className="mx-auto mb-2 h-8 w-8 text-red-600 dark:text-red-400" />
                            <p className="text-sm text-red-800 dark:text-red-200">{error}</p>
                        </div>
                    ) : filteredTrainees.length > 0 ? (
                        <div className="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Trainee</TableHead>
                                        <TableHead>Completed</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {filteredTrainees.map((trainee) => (
                                        <TableRow key={trainee.id}>
                                            <TableCell>
                                                <div className="flex items-center gap-3">
                                                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 font-medium text-primary">
                                                        {getInitials(trainee.name)}
                                                    </div>
                                                    <div className="flex flex-col">
                                                        <Link
                                                            href={route('users.profile', trainee.vatsim_id)}
                                                            className="font-medium hover:underline"
                                                        >
                                                            {trainee.name}
                                                        </Link>
                                                        <a
                                                            href={`https://stats.vatsim.net/stats/${trainee.vatsim_id}`}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="text-sm text-muted-foreground hover:underline"
                                                        >
                                                            {trainee.vatsim_id}
                                                        </a>
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                                    <Calendar className="h-4 w-4" />
                                                    {new Date(trainee.completed_at).toLocaleDateString('de')}
                                                </div>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button size="sm" variant="outline" onClick={() => handleReactivate(trainee.id)}>
                                                    <UserPlus className="mr-2 h-4 w-4" />
                                                    Reactivate
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    ) : (
                        <div className="rounded-lg border border-dashed py-12 text-center">
                            <Search className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                            <h3 className="mb-2 text-lg font-semibold">No past trainees found</h3>
                            <p className="text-sm text-muted-foreground">
                                {searchTerm ? 'Try adjusting your search' : 'No trainees have completed this course yet'}
                            </p>
                        </div>
                    )}
                </div>

                <div className="flex items-center justify-between border-t pt-4">
                    <div className="text-sm text-muted-foreground">
                        Showing {filteredTrainees.length} of {trainees.length} past trainees
                    </div>
                    <Button variant="outline" onClick={handleClose}>
                        Close
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}
