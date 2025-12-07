import AppLayout from '@/layouts/app-layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger, DialogFooter, DialogDescription } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { router } from '@inertiajs/react';
import { Clock, Play, Search, Users, X, Eye, MessageSquare } from 'lucide-react';
import { useMemo, useState } from 'react';
import { cn } from '@/lib/utils';
import { useIsMobile } from '@/hooks/use-mobile';
import { Drawer, DrawerContent, DrawerDescription, DrawerHeader, DrawerTitle, DrawerTrigger } from '@/components/ui/drawer';
import { toast } from 'sonner';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { getTypeColor } from '@/lib/course-utils';

interface WaitingListEntry {
    id: number;
    name: string;
    vatsim_id: number;
    activity: number;
    waiting_time: string;
    waiting_days: number;
    remarks?: string;
    date_added: string;
}

interface Course {
    id: number;
    name: string;
    type: string;
    type_display: string;
    position: string;
    position_display: string;
    waiting_count: number;
    waiting_list: WaitingListEntry[];
}

interface PageProps {
    courses: Course[];
    statistics: {
        total_waiting: number;
        rtg_waiting: number;
        edmt_waiting: number;
        fam_waiting: number;
        gst_waiting: number;
    };
    config: {
        min_activity: number;
        display_activity: number;
    };
}

export default function MentorWaitingLists({ courses, config }: PageProps) {
    const [selectedCourse, setSelectedCourse] = useState<Course | null>(null);
    const [selectedEntry, setSelectedEntry] = useState<WaitingListEntry | null>(null);
    const [remarks, setRemarks] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [typeFilter, setTypeFilter] = useState('all');
    const isMobile = useIsMobile();
    const [isRemarksDialogOpen, setIsRemarksDialogOpen] = useState(false);
    const [isStartTrainingDialogOpen, setIsStartTrainingDialogOpen] = useState(false);
    const [entryToStart, setEntryToStart] = useState<WaitingListEntry | null>(null);

    const handleStartTrainingClick = (entry: WaitingListEntry) => {
        setEntryToStart(entry);
        setIsStartTrainingDialogOpen(true);
    };

    const handleConfirmStartTraining = async () => {
        if (!entryToStart || isLoading || !selectedCourse) return;

        setIsLoading(true);

        const savedEntry = entryToStart;
        const originalWaitingList = selectedCourse.waiting_list;

        const updatedWaitingList = selectedCourse.waiting_list.filter((entry) => entry.id !== savedEntry.id);

        setSelectedCourse({
            ...selectedCourse,
            waiting_list: updatedWaitingList,
            waiting_count: updatedWaitingList.length,
        });

        setIsStartTrainingDialogOpen(false);
        setEntryToStart(null);

        router.post(
            `/waiting-lists/${savedEntry.id}/start-training`,
            {},
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Training started successfully');
                },
                onError: (errors) => {
                    setSelectedCourse({
                        ...selectedCourse,
                        waiting_list: originalWaitingList,
                        waiting_count: originalWaitingList.length,
                    });

                    const errorMessage = Object.values(errors).flat()[0] || 'Failed to start training';
                    toast.error(typeof errorMessage === 'string' ? errorMessage : 'Failed to start training');
                },
                onFinish: () => {
                    setIsLoading(false);
                },
            },
        );
    };

    const handleUpdateRemarks = async () => {
        if (!selectedEntry || isLoading || !selectedCourse) return;

        setIsLoading(true);

        const originalRemarks = selectedEntry.remarks;
        const savedEntry = selectedEntry;
        const savedRemarks = remarks;

        const updatedWaitingList = selectedCourse.waiting_list.map((entry) =>
            entry.id === savedEntry.id ? { ...entry, remarks: savedRemarks } : entry,
        );

        setSelectedCourse({
            ...selectedCourse,
            waiting_list: updatedWaitingList,
        });

        setIsRemarksDialogOpen(false);
        setSelectedEntry(null);
        setRemarks('');

        router.post(
            '/waiting-lists/update-remarks',
            {
                entry_id: savedEntry.id,
                remarks: savedRemarks,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Remarks updated successfully');
                },
                onError: (errors) => {
                    const revertedWaitingList = selectedCourse.waiting_list.map((entry) =>
                        entry.id === savedEntry.id ? { ...entry, remarks: originalRemarks } : entry,
                    );

                    setSelectedCourse({
                        ...selectedCourse,
                        waiting_list: revertedWaitingList,
                    });

                    const errorMessage = Object.values(errors).flat()[0] || 'Failed to update remarks';
                    toast.error(typeof errorMessage === 'string' ? errorMessage : 'Failed to update remarks');
                },
                onFinish: () => {
                    setIsLoading(false);
                },
            },
        );
    };

    const handleCancelRemarks = () => {
        setIsRemarksDialogOpen(false);
        setSelectedEntry(null);
        setRemarks('');
    };

    const filteredCourses = useMemo(() => {
        return courses.filter((course) => {
            const matchesSearch =
                !searchTerm ||
                course.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                course.position_display.toLowerCase().includes(searchTerm.toLowerCase());

            const matchesType = typeFilter === 'all' || course.type === typeFilter;

            return matchesSearch && matchesType;
        });
    }, [courses, searchTerm, typeFilter]);

    return (
        <AppLayout breadcrumbs={[{ title: 'Waiting Lists', href: route('waiting-lists.manage') }]}>
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-center gap-3">
                    <div className="relative min-w-[300px] flex-1">
                        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input placeholder="Search courses..." value={searchTerm} onChange={(e) => setSearchTerm(e.target.value)} className="pl-10" />
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

                    <Tabs value={typeFilter} onValueChange={setTypeFilter}>
                        <TabsList>
                            <TabsTrigger value="all">All</TabsTrigger>
                            <TabsTrigger value="RTG">Rating</TabsTrigger>
                            <TabsTrigger value="EDMT">Endorsement</TabsTrigger>
                            <TabsTrigger value="FAM">Familiarisation</TabsTrigger>
                            <TabsTrigger value="GST">Visitor</TabsTrigger>
                            <TabsTrigger value="RST">Roster</TabsTrigger>
                        </TabsList>
                    </Tabs>
                </div>

                {filteredCourses.length > 0 ? (
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {filteredCourses.map((course) => (
                            <Card key={course.id}>
                                <CardHeader>
                                    <div className="flex items-start gap-3">
                                        <div className="min-w-0 flex-1">
                                            <CardTitle className="truncate text-base">{course.name}</CardTitle>
                                            <CardDescription className="mt-1 flex flex-wrap gap-2">
                                                <Badge variant="outline" className="text-xs">
                                                    {course.position_display}
                                                </Badge>
                                                <Badge className={cn('text-xs', getTypeColor(course.type))}>{course.type_display}</Badge>
                                            </CardDescription>
                                        </div>
                                    </div>
                                </CardHeader>

                                <CardContent className="-mt-4 space-y-3">
                                    <div className="flex items-center justify-between rounded-lg border p-3">
                                        <div className="flex items-center gap-2">
                                            <Users className="h-4 w-4 text-muted-foreground" />
                                            <span className="text-sm text-muted-foreground">Trainees waiting</span>
                                        </div>
                                        <Badge variant={course.waiting_count > 0 ? 'default' : 'secondary'}>{course.waiting_count}</Badge>
                                    </div>

                                    {isMobile ? (
                                        <Drawer>
                                            <DrawerTrigger asChild>
                                                <Button
                                                    className="w-full"
                                                    variant={course.waiting_count > 0 ? 'default' : 'outline'}
                                                    disabled={course.waiting_count === 0}
                                                    onClick={() => setSelectedCourse(course)}
                                                >
                                                    <Eye className="h-4 w-4" />
                                                    View Waiting List
                                                </Button>
                                            </DrawerTrigger>

                                            <DrawerContent className="max-h-[85vh]">
                                                <DrawerHeader>
                                                    <DrawerTitle className="flex items-center gap-2">{course.name}</DrawerTitle>
                                                    <DrawerDescription>
                                                        {course.waiting_count} trainee{course.waiting_count !== 1 ? 's' : ''} waiting for training
                                                    </DrawerDescription>
                                                </DrawerHeader>

                                                <div className="overflow-y-auto px-4 pb-4">
                                                    {selectedCourse && selectedCourse.waiting_list.length > 0 ? (
                                                        <div className="space-y-3">
                                                            {selectedCourse.waiting_list.map((entry, index) => (
                                                                <Card key={entry.id}>
                                                                    <CardContent className="space-y-3 p-4">
                                                                        <div className="flex items-start justify-between">
                                                                            <div>
                                                                                <div className="font-medium">{entry.name}</div>
                                                                                <div className="text-sm text-muted-foreground">{entry.vatsim_id}</div>
                                                                                <Badge variant="secondary">#{index + 1}</Badge>
                                                                            </div>
                                                                            <Badge
                                                                                className={cn(
                                                                                    entry.activity >= config.min_activity &&
                                                                                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                                                                                    entry.activity >= config.display_activity &&
                                                                                        entry.activity < config.min_activity &&
                                                                                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                                                                    entry.activity < config.display_activity &&
                                                                                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                                                                )}
                                                                            >
                                                                                {entry.activity}h
                                                                            </Badge>
                                                                        </div>

                                                                        <div className="flex items-center gap-1 text-sm text-muted-foreground">
                                                                            <Clock className="h-3 w-3" />
                                                                            {entry.waiting_time}
                                                                        </div>

                                                                        {entry.remarks && (
                                                                            <div className="border-l-2 pl-3 text-sm text-muted-foreground">
                                                                                {entry.remarks}
                                                                            </div>
                                                                        )}

                                                                        <div className="flex gap-2 pt-2">
                                                                            <TooltipProvider>
                                                                                <Tooltip>
                                                                                    <TooltipTrigger asChild>
                                                                                        <div>
                                                                                            <Button
                                                                                                size="sm"
                                                                                                className="flex-1"
                                                                                                onClick={() => handleStartTrainingClick(entry)}
                                                                                                disabled={
                                                                                                    isLoading ||
                                                                                                    (selectedCourse.type === 'RTG' &&
                                                                                                        entry.activity < config.display_activity)
                                                                                                }
                                                                                            >
                                                                                                <Play className="mr-1 h-4 w-4" />
                                                                                                Start Training
                                                                                            </Button>
                                                                                        </div>
                                                                                    </TooltipTrigger>
                                                                                    {selectedCourse.type === 'RTG' &&
                                                                                        entry.activity < config.display_activity && (
                                                                                            <TooltipContent side="top">
                                                                                                <p>
                                                                                                    Trainee needs at least {config.display_activity}h
                                                                                                    of activity to start a rating course
                                                                                                </p>
                                                                                            </TooltipContent>
                                                                                        )}
                                                                                </Tooltip>
                                                                            </TooltipProvider>
                                                                            <Button
                                                                                size="sm"
                                                                                variant="outline"
                                                                                onClick={() => {
                                                                                    setSelectedEntry(entry);
                                                                                    setRemarks(entry.remarks || '');
                                                                                    setIsRemarksDialogOpen(true);
                                                                                }}
                                                                            >
                                                                                <MessageSquare className="h-4 w-4" />
                                                                            </Button>
                                                                        </div>
                                                                    </CardContent>
                                                                </Card>
                                                            ))}
                                                        </div>
                                                    ) : (
                                                        <div className="py-8 text-center text-muted-foreground">
                                                            No trainees waiting for this course
                                                        </div>
                                                    )}
                                                </div>
                                            </DrawerContent>
                                        </Drawer>
                                    ) : (
                                        <Dialog>
                                            <DialogTrigger asChild>
                                                <Button
                                                    className="w-full"
                                                    variant={course.waiting_count > 0 ? 'default' : 'outline'}
                                                    disabled={course.waiting_count === 0}
                                                    onClick={() => setSelectedCourse(course)}
                                                >
                                                    <Eye className="h-4 w-4" />
                                                    View Waiting List
                                                </Button>
                                            </DialogTrigger>

                                            <DialogContent className="max-h-[85vh] overflow-y-auto sm:max-w-[90vw] lg:max-w-[1000px]">
                                                <DialogHeader>
                                                    <DialogTitle className="flex items-center gap-2">{course.name}</DialogTitle>
                                                    <DialogDescription>
                                                        {course.waiting_count} trainee{course.waiting_count !== 1 ? 's' : ''} waiting for training
                                                    </DialogDescription>
                                                </DialogHeader>

                                                {selectedCourse && selectedCourse.waiting_list.length > 0 ? (
                                                    <div className="rounded-md border">
                                                        <Table>
                                                            <TableHeader>
                                                                <TableRow>
                                                                    <TableHead>Position</TableHead>
                                                                    <TableHead>Trainee</TableHead>
                                                                    <TableHead>Activity</TableHead>
                                                                    <TableHead>Waiting Time</TableHead>
                                                                    <TableHead>Remarks</TableHead>
                                                                    <TableHead className="text-right">Actions</TableHead>
                                                                </TableRow>
                                                            </TableHeader>
                                                            <TableBody>
                                                                {selectedCourse.waiting_list.map((entry, index) => (
                                                                    <TableRow key={entry.id}>
                                                                        <TableCell className="pl-3 font-medium">{index + 1}</TableCell>
                                                                        <TableCell>
                                                                            <div>
                                                                                <div className="font-medium">{entry.name}</div>
                                                                                <div className="text-sm text-muted-foreground">{entry.vatsim_id}</div>
                                                                            </div>
                                                                        </TableCell>
                                                                        <TableCell>
                                                                            <span
                                                                                className={cn(
                                                                                    'font-medium',
                                                                                    entry.activity >= config.min_activity && 'text-green-600',
                                                                                    entry.activity >= config.display_activity &&
                                                                                        entry.activity < config.min_activity &&
                                                                                        'text-yellow-600',
                                                                                    entry.activity < config.display_activity && 'text-red-600',
                                                                                )}
                                                                            >
                                                                                {entry.activity}h
                                                                            </span>
                                                                        </TableCell>
                                                                        <TableCell>
                                                                            <div className="flex items-center gap-1 text-sm text-muted-foreground">
                                                                                <Clock className="h-3 w-3" />
                                                                                {entry.waiting_time}
                                                                            </div>
                                                                        </TableCell>
                                                                        <TableCell>
                                                                            {entry.remarks ? (
                                                                                <span className="text-sm text-muted-foreground">
                                                                                    {entry.remarks.substring(0, 30)}
                                                                                    {entry.remarks.length > 30 && '...'}
                                                                                </span>
                                                                            ) : (
                                                                                <span className="text-sm text-muted-foreground">—</span>
                                                                            )}
                                                                        </TableCell>
                                                                        <TableCell className="text-right">
                                                                            <div className="flex justify-end gap-2">
                                                                                <TooltipProvider>
                                                                                    <Tooltip>
                                                                                        <TooltipTrigger asChild>
                                                                                            <div>
                                                                                                <Button
                                                                                                    size="sm"
                                                                                                    className="flex-1"
                                                                                                    onClick={() => handleStartTrainingClick(entry)}
                                                                                                    disabled={
                                                                                                        isLoading ||
                                                                                                        (selectedCourse.type === 'RTG' &&
                                                                                                            entry.activity < config.display_activity)
                                                                                                    }
                                                                                                >
                                                                                                    <Play className="mr-1 h-4 w-4" />
                                                                                                    Start Training
                                                                                                </Button>
                                                                                            </div>
                                                                                        </TooltipTrigger>
                                                                                        {selectedCourse.type === 'RTG' &&
                                                                                            entry.activity < config.display_activity && (
                                                                                                <TooltipContent side="top">
                                                                                                    <p>
                                                                                                        Trainee needs at least{' '}
                                                                                                        {config.display_activity}h of activity to
                                                                                                        start a rating course
                                                                                                    </p>
                                                                                                </TooltipContent>
                                                                                            )}
                                                                                    </Tooltip>
                                                                                </TooltipProvider>
                                                                                <Button
                                                                                    size="sm"
                                                                                    variant="outline"
                                                                                    onClick={() => {
                                                                                        setSelectedEntry(entry);
                                                                                        setRemarks(entry.remarks || '');
                                                                                        setIsRemarksDialogOpen(true);
                                                                                    }}
                                                                                >
                                                                                    <MessageSquare className="h-4 w-4" />
                                                                                </Button>
                                                                            </div>
                                                                        </TableCell>
                                                                    </TableRow>
                                                                ))}
                                                            </TableBody>
                                                        </Table>
                                                    </div>
                                                ) : (
                                                    <div className="py-8 text-center text-muted-foreground">No trainees waiting for this course</div>
                                                )}
                                            </DialogContent>
                                        </Dialog>
                                    )}
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                ) : (
                    <Card className="py-12">
                        <CardContent className="text-center">
                            <Search className="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                            <h3 className="mb-2 text-lg font-semibold">No courses found</h3>
                            <p className="text-muted-foreground">Try adjusting your search or filter criteria.</p>
                        </CardContent>
                    </Card>
                )}
            </div>

            <Dialog open={isStartTrainingDialogOpen} onOpenChange={setIsStartTrainingDialogOpen}>
                <DialogContent className="gap-6">
                    <DialogHeader>
                        <DialogTitle>Start Training</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to start training for <span className="font-medium">{entryToStart?.name}</span>?
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsStartTrainingDialogOpen(false)} disabled={isLoading}>
                            Cancel
                        </Button>
                        <Button onClick={handleConfirmStartTraining} disabled={isLoading}>
                            {isLoading ? 'Starting...' : 'Start Training'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={isRemarksDialogOpen} onOpenChange={setIsRemarksDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Update Remarks – {selectedEntry?.name}</DialogTitle>
                        <DialogDescription>Add or update notes.</DialogDescription>
                    </DialogHeader>

                    <Textarea placeholder="Enter remarks..." value={remarks} onChange={(e) => setRemarks(e.target.value)} rows={4} />

                    <DialogFooter>
                        <Button variant="outline" onClick={handleCancelRemarks}>
                            Cancel
                        </Button>

                        <Button onClick={handleUpdateRemarks} disabled={isLoading}>
                            {isLoading ? 'Saving...' : 'Save'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}