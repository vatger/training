import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { MentorCourse, Trainee } from '@/types/mentor';
import { Link, router } from '@inertiajs/react';
import {
    CheckCircle2,
    Clock,
    Eye,
    FileText,
    MoreVertical,
    Plus,
    UserCheck,
    UserMinus,
    UserPlus,
    Users,
    ChevronUp,
    ChevronDown,
    Award,
    CheckCircle,
    AlertCircle,
    Loader2,
} from 'lucide-react';
import { useState, useEffect } from 'react';
import { ColumnDef, flexRender, getCoreRowModel, useReactTable, VisibilityState } from '@tanstack/react-table';
import { SoloModal } from './solo-modal';
import { ProgressModal } from './progress-modal';
import { useMoodleStatus } from '@/hooks/use-moodle-status';

interface TraineeDataTableProps {
    trainees: Trainee[];
    course: MentorCourse;
    onRemarkClick: (trainee: Trainee) => void;
    onClaimClick: (trainee: Trainee) => void;
    onAssignClick: (trainee: Trainee) => void;
}

function TraineeRowActions({
    trainee,
    courseId,
    rowIndex,
    totalRows,
    onClaimClick,
    onAssignClick,
    onMoveUp,
    onMoveDown,
}: {
    trainee: Trainee;
    courseId: number;
    rowIndex: number;
    totalRows: number;
    onRemarkClick: (trainee: Trainee) => void;
    onClaimClick: (trainee: Trainee) => void;
    onAssignClick: (trainee: Trainee) => void;
    onMoveUp: () => void;
    onMoveDown: () => void;
}) {
    const [isRemoving, setIsRemoving] = useState(false);
    const [isFinishing, setIsFinishing] = useState(false);
    const [removeOpen, setRemoveOpen] = useState(false);
    const [finishOpen, setFinishOpen] = useState(false);
    const [dropdownOpen, setDropdownOpen] = useState(false);

    const isFirst = rowIndex === 0;
    const isLast = rowIndex === totalRows - 1;

    const handleRemoveTrainee = () => {
        setIsRemoving(true);
        router.post(
            route('overview.remove-trainee'),
            {
                trainee_id: trainee.id,
                course_id: courseId,
            },
            {
                onFinish: () => {
                    setIsRemoving(false);
                    setRemoveOpen(false);
                },
            },
        );
    };

    const handleFinishTrainee = () => {
        setIsFinishing(true);
        router.post(
            route('overview.finish-trainee'),
            {
                trainee_id: trainee.id,
                course_id: courseId,
            },
            {
                onFinish: () => setIsFinishing(false),
            },
        );
    };

    const handleMoveUp = (e: React.MouseEvent) => {
        e.preventDefault();
        onMoveUp();
    };

    const handleMoveDown = (e: React.MouseEvent) => {
        e.preventDefault();
        onMoveDown();
    };

    return (
        <>
            <div className="flex items-center justify-end gap-2">
                <Button size="sm" variant="success" onClick={() => setFinishOpen(true)}>
                    <CheckCircle2 className="mr-1 h-3 w-3" />
                    Finish
                </Button>
                <DropdownMenu open={dropdownOpen} onOpenChange={setDropdownOpen}>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="sm">
                            <MoreVertical className="h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem asChild>
                            <Link href={`/users/${trainee.vatsimId}`}>
                                <FileText className="h-4 w-4" />
                                View Profile
                            </Link>
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        {trainee.claimedBy !== 'You' && (
                            <DropdownMenuItem onClick={() => onClaimClick(trainee)}>
                                <UserPlus className="h-4 w-4" />
                                Claim Trainee
                            </DropdownMenuItem>
                        )}
                        <DropdownMenuItem onClick={() => onAssignClick(trainee)}>
                            <Users className="h-4 w-4" />
                            Assign to Mentor
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            disabled={isFirst}
                            onSelect={(e) => {
                                e.preventDefault();
                                handleMoveUp(e as any);
                            }}
                        >
                            <ChevronUp className="h-4 w-4" />
                            Move Up
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            disabled={isLast}
                            onSelect={(e) => {
                                e.preventDefault();
                                handleMoveDown(e as any);
                            }}
                        >
                            <ChevronDown className="h-4 w-4" />
                            Move Down
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem className="text-destructive focus:text-destructive" onClick={() => setRemoveOpen(true)}>
                            <UserMinus className="h-4 w-4 text-destructive" />
                            Remove from Course
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
            <Dialog open={removeOpen} onOpenChange={setRemoveOpen}>
                <DialogContent className="gap-6">
                    <DialogHeader>
                        <DialogTitle>Remove Trainee</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to remove <span className="font-medium">{trainee?.name}</span> from this course?
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setRemoveOpen(false)} disabled={isRemoving}>
                            Cancel
                        </Button>
                        <Button onClick={handleRemoveTrainee} disabled={isRemoving} variant="destructive">
                            {isRemoving ? 'Removing...' : 'Remove from Course'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
            <Dialog open={finishOpen} onOpenChange={setFinishOpen}>
                <DialogContent className="gap-6">
                    <DialogHeader>
                        <DialogTitle>Complete Training</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to give <span className="font-medium">{trainee?.name}</span> all of the endorsements for this
                            course?
                            <br />
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setFinishOpen(false)} disabled={isFinishing}>
                            Cancel
                        </Button>
                        <Button onClick={handleFinishTrainee} disabled={isFinishing} variant={'success'}>
                            {isFinishing ? 'Completing...' : 'Complete Training'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

export function TraineeDataTable({ trainees, course, onRemarkClick, onClaimClick, onAssignClick }: TraineeDataTableProps) {
    const [data, setData] = useState<Trainee[]>(trainees);
    const [isUnclaiming, setIsUnclaiming] = useState<number | null>(null);
    const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({});

    const [soloModalOpen, setSoloModalOpen] = useState(false);
    const [selectedTraineeForSolo, setSelectedTraineeForSolo] = useState<Trainee | null>(null);

    const [grantModalOpen, setGrantModalOpen] = useState(false);
    const [selectedTraineeForGrant, setSelectedTraineeForGrant] = useState<Trainee | null>(null);
    const [isGrantingEndorsement, setIsGrantingEndorsement] = useState(false);

    const [progressModalOpen, setProgressModalOpen] = useState(false);
    const [selectedTraineeForProgress, setSelectedTraineeForProgress] = useState<Trainee | null>(null);

    const traineeData = trainees.map((t) => ({ id: t.id, vatsimId: t.vatsimId }));
    const { statuses: moodleStatuses, loading: moodleLoading } = useMoodleStatus(traineeData, course.id);

    useEffect(() => {
        setData(trainees);
    }, [trainees]);

    useEffect(() => {
        const visibility: VisibilityState = {
            solo: course.type === 'RTG' && course.position !== 'GND',
            endorsement: course.type === 'RTG' && course.position === 'GND',
            moodleStatus: course.type === 'GST' || course.type === 'EDMT',
        };
        setColumnVisibility(visibility);
    }, [course]);

    const handleUnclaimTrainee = (trainee: Trainee) => {
        setIsUnclaiming(trainee.id);
        router.post(
            route('overview.unclaim-trainee'),
            {
                trainee_id: trainee.id,
                course_id: course.id,
            },
            {
                onFinish: () => setIsUnclaiming(null),
            },
        );
    };

    const handleGrantEndorsement = () => {
        if (!selectedTraineeForGrant) return;

        setIsGrantingEndorsement(true);
        router.post(
            route('overview.grant-endorsement'),
            {
                trainee_id: selectedTraineeForGrant.id,
                course_id: course.id,
            },
            {
                preserveState: true,
                preserveScroll: true,
                only: ['courses'],
                onFinish: () => {
                    setIsGrantingEndorsement(false);
                    setGrantModalOpen(false);
                    setSelectedTraineeForGrant(null);
                },
            },
        );
    };

    const formatRemarkDate = (dateString: string | null) => {
        if (!dateString) return '';
        const date = new Date(dateString);
        const now = new Date();
        const diffInDays = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60 * 24));

        if (diffInDays === 0) return 'Today';
        if (diffInDays === 1) return 'Yesterday';
        if (diffInDays < 7) return `${diffInDays} days ago`;
        if (diffInDays < 30) return `${Math.floor(diffInDays / 7)} weeks ago`;
        if (diffInDays < 365) return `${Math.floor(diffInDays / 30)} months ago`;
        return date.toLocaleDateString('de');
    };

    const moveTrainee = (index: number, direction: 'up' | 'down') => {
        const newData = [...data];
        const newIndex = direction === 'up' ? index - 1 : index + 1;

        if (newIndex < 0 || newIndex >= newData.length) return;

        [newData[index], newData[newIndex]] = [newData[newIndex], newData[index]];

        setData(newData);

        const traineeIds = newData.map((t) => t.id);

        router.post(
            route('overview.update-trainee-order'),
            {
                course_id: course.id,
                trainee_ids: traineeIds,
            },
            {
                preserveScroll: true,
                preserveState: true,
                onError: (errors) => {
                    console.error('Failed to update trainee order:', errors);
                    setData(trainees);
                },
            },
        );
    };

    const columns: ColumnDef<Trainee>[] = [
        {
            id: 'trainee',
            accessorKey: 'name',
            header: 'Trainee',
            cell: ({ row }) => {
                const trainee = row.original;
                return (
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 font-medium text-primary">
                            {trainee.initials}
                        </div>
                        <div className="flex flex-col">
                            <Link href={`/users/${trainee.vatsimId}`} className="font-medium hover:underline">
                                {trainee.name}
                            </Link>
                            <a
                                href={`https://stats.vatsim.net/stats/${trainee.vatsimId}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="text-sm text-muted-foreground hover:underline"
                            >
                                {trainee.vatsimId}
                            </a>
                        </div>
                    </div>
                );
            },
        },
        {
            id: 'progress',
            accessorKey: 'progress',
            header: 'Progress',
            cell: ({ row }) => {
                const trainee = row.original;
                return (
                    <div className="space-y-1">
                        {trainee.progress.length > 0 ? (
                            <div className="flex items-center gap-1">
                                {trainee.progress.slice(0, 5).map((passed, idx) => (
                                    <div
                                        key={idx}
                                        className={`h-2 w-2 rounded-full ${passed ? 'bg-green-500' : 'bg-red-500'}`}
                                        title={`Session ${idx + 1}: ${passed ? 'Passed' : 'Failed'}`}
                                    />
                                ))}
                                {trainee.progress.length > 5 && (
                                    <span className="ml-1 text-xs text-muted-foreground">+{trainee.progress.length - 5}</span>
                                )}
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="ml-1 h-6 px-2"
                                    onClick={() => {
                                        setSelectedTraineeForProgress(trainee);
                                        setProgressModalOpen(true);
                                    }}
                                >
                                    <Eye className="mr-1 h-3 w-3" />
                                    Details
                                </Button>
                                <Button
                                    variant="success"
                                    size="sm"
                                    className="size-6"
                                    onClick={() => {
                                        router.visit(
                                            route('training-logs.create', {
                                                traineeId: trainee.id,
                                                courseId: course.id,
                                            }),
                                        );
                                    }}
                                >
                                    <Plus className="h-3 w-3" />
                                </Button>
                            </div>
                        ) : (
                            <div className="flex items-center gap-1">
                                <span className="text-sm text-muted-foreground">No sessions yet</span>
                                <Button
                                    variant="outline"
                                    size="icon"
                                    className="size-6"
                                    onClick={() => {
                                        router.visit(
                                            route('training-logs.create', {
                                                traineeId: trainee.id,
                                                courseId: course.id,
                                            }),
                                        );
                                    }}
                                >
                                    <Plus className="h-3 w-3" />
                                </Button>
                            </div>
                        )}
                        {trainee.lastSession && (
                            <div className="text-xs text-muted-foreground">Last: {new Date(trainee.lastSession).toLocaleDateString('de')}</div>
                        )}
                    </div>
                );
            },
        },
        {
            id: 'solo',
            accessorKey: 'soloStatus',
            header: 'Solo',
            cell: ({ row }) => {
                const trainee = row.original;
                return trainee.soloStatus ? (
                    <Button
                        onClick={() => {
                            setSelectedTraineeForSolo(trainee);
                            setSoloModalOpen(true);
                        }}
                        variant="outline"
                        size="sm"
                        className={
                            trainee.soloStatus.remaining < 10
                                ? 'border-red-200 bg-red-50 text-red-700 hover:bg-red-100'
                                : trainee.soloStatus.remaining < 20
                                  ? 'border-yellow-200 bg-yellow-50 text-yellow-700 hover:bg-yellow-100'
                                  : 'border border-green-200 bg-green-200 text-green-700 shadow-xs hover:bg-green-100 hover:text-green-700'
                        }
                    >
                        <Clock className="mr-1 h-3 w-3" />
                        {trainee.soloStatus.remaining} days
                    </Button>
                ) : (
                    <Button
                        onClick={() => {
                            setSelectedTraineeForSolo(trainee);
                            setSoloModalOpen(true);
                        }}
                        variant="ghost"
                        size="sm"
                        className="h-7 text-xs"
                    >
                        <Plus className="mr-1 h-3 w-3" />
                        Add Solo
                    </Button>
                );
            },
        },
        {
            id: 'endorsement',
            accessorKey: 'endorsementStatus',
            header: 'Endorsement',
            cell: ({ row }) => {
                const trainee = row.original;
                const endorsementStatus = (trainee as any).endorsementStatus;

                return endorsementStatus ? (
                    <Badge variant="outline" className="border-green-200 bg-green-50 text-green-700">
                        <Award className="mr-1 h-3 w-3" />
                        {endorsementStatus}
                    </Badge>
                ) : (
                    <Button
                        onClick={() => {
                            setSelectedTraineeForGrant(trainee);
                            setGrantModalOpen(true);
                        }}
                        variant="ghost"
                        size="sm"
                        className="h-7 text-xs"
                    >
                        <CheckCircle2 className="h-3 w-3" />
                        Grant Endorsement
                    </Button>
                );
            },
        },
        {
            id: 'moodleStatus',
            accessorKey: 'moodleStatus',
            header: 'Moodle Status',
            cell: ({ row }) => {
                const trainee = row.original;
                const cacheKey = `${trainee.vatsimId}_${course.id}`;
                const moodleStatus = moodleStatuses[cacheKey];

                if (moodleLoading && !moodleStatus) {
                    return (
                        <Badge variant="outline" className="border-gray-200 bg-gray-50 text-gray-700">
                            <Loader2 className="mr-1 h-3 w-3 animate-spin" />
                            Loading...
                        </Badge>
                    );
                }

                if (!moodleStatus) {
                    return <span className="text-sm text-muted-foreground">—</span>;
                }

                const getStatusConfig = (status: string) => {
                    switch (status) {
                        case 'completed':
                            return {
                                className: 'border-green-200 bg-green-50 text-green-700 dark:border-green-700 dark:bg-green-900 dark:text-green-300',
                                label: 'Completed',
                                icon: <CheckCircle className="mr-1 h-3 w-3" />,
                            };
                        case 'in-progress':
                            return {
                                className: 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-700 dark:bg-blue-900 dark:text-blue-300',
                                label: 'In Progress',
                                icon: <Clock className="mr-1 h-3 w-3" />,
                            };
                        case 'not-started':
                            return {
                                className: 'border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300',
                                label: 'Not Started',
                                icon: <AlertCircle className="mr-1 h-3 w-3" />,
                            };
                        default:
                            return {
                                className:
                                    'border-yellow-200 bg-yellow-50 text-yellow-700 dark:border-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
                                label: 'Unknown',
                                icon: <AlertCircle className="mr-1 h-3 w-3" />,
                            };
                    }
                };

                const config = getStatusConfig(moodleStatus);

                return (
                    <Badge variant="outline" className={config.className}>
                        {config.icon}
                        {config.label}
                    </Badge>
                );
            },
        },
        {
            id: 'nextStep',
            accessorKey: 'nextStep',
            header: 'Next Step',
            cell: ({ row }) => {
                const trainee = row.original;
                return <div className="max-w-xs truncate text-sm">{trainee.nextStep || '—'}</div>;
            },
        },
        {
            id: 'remark',
            accessorKey: 'remark',
            header: 'Remark',
            cell: ({ row }) => {
                const trainee = row.original;
                return (
                    <TooltipProvider>
                        <Tooltip delayDuration={500}>
                            <TooltipTrigger asChild>
                                <button
                                    onClick={() => onRemarkClick(trainee)}
                                    className="max-w-76 rounded p-1 text-left transition-colors hover:bg-muted/64"
                                >
                                    {trainee.remark && trainee.remark.text ? (
                                        <div>
                                            <div className="line-clamp-2 text-sm">{trainee.remark.text}</div>
                                            <div className="mt-1 text-xs text-muted-foreground">Click to edit</div>
                                        </div>
                                    ) : (
                                        <div className="text-sm text-muted-foreground">Click to add remark</div>
                                    )}
                                </button>
                            </TooltipTrigger>
                            {trainee.remark && trainee.remark.text && trainee.remark.updated_at && (
                                <TooltipContent side="top" className="max-w-xs">
                                    <div className="space-y-1">
                                        <div className="font-medium">Last updated</div>
                                        <div className="text-sm">
                                            {formatRemarkDate(trainee.remark.updated_at)}
                                            {trainee.remark.author_name && (
                                                <>
                                                    {' by '}
                                                    <span className="font-medium">{trainee.remark.author_name}</span>
                                                </>
                                            )}
                                        </div>
                                    </div>
                                </TooltipContent>
                            )}
                        </Tooltip>
                    </TooltipProvider>
                );
            },
        },
        {
            id: 'status',
            accessorKey: 'claimedBy',
            header: 'Status',
            cell: ({ row }) => {
                const trainee = row.original;
                const isClaiming = isUnclaiming === trainee.id;

                return trainee.claimedBy ? (
                    <TooltipProvider>
                        <Tooltip delayDuration={200}>
                            <TooltipTrigger asChild>
                                <Badge
                                    variant="outline"
                                    className={
                                        trainee.claimedBy === 'You'
                                            ? 'cursor-pointer border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100'
                                            : 'border-gray-200 bg-gray-50 text-gray-700'
                                    }
                                    onClick={trainee.claimedBy === 'You' && !isClaiming ? () => handleUnclaimTrainee(trainee) : undefined}
                                >
                                    {trainee.claimedBy === 'You' ? (
                                        <>
                                            <UserCheck className="mr-1 h-3 w-3" />
                                            {isClaiming ? 'Unclaiming...' : 'Claimed by you'}
                                        </>
                                    ) : (
                                        <>
                                            <Users className="mr-1 h-3 w-3" />
                                            Claimed by {trainee.claimedBy}
                                        </>
                                    )}
                                </Badge>
                            </TooltipTrigger>
                            {trainee.claimedBy === 'You' && (
                                <TooltipContent>
                                    <p>Click to unclaim trainee</p>
                                </TooltipContent>
                            )}
                        </Tooltip>
                    </TooltipProvider>
                ) : (
                    <Button variant="outline" size="sm" onClick={() => onClaimClick(trainee)}>
                        <UserPlus className="mr-1 h-3 w-3" />
                        Claim
                    </Button>
                );
            },
        },
        {
            id: 'actions',
            header: () => <div className="text-right">Actions</div>,
            cell: ({ row }) => {
                const trainee = row.original;
                const index = row.index;
                return (
                    <TraineeRowActions
                        trainee={trainee}
                        courseId={course.id}
                        rowIndex={index}
                        totalRows={data.length}
                        onRemarkClick={onRemarkClick}
                        onClaimClick={onClaimClick}
                        onAssignClick={onAssignClick}
                        onMoveUp={() => moveTrainee(index, 'up')}
                        onMoveDown={() => moveTrainee(index, 'down')}
                    />
                );
            },
        },
    ];

    const table = useReactTable({
        data,
        columns,
        getCoreRowModel: getCoreRowModel(),
        state: {
            columnVisibility,
        },
        onColumnVisibilityChange: setColumnVisibility,
    });

    return (
        <div className="overflow-x-auto">
            <Table>
                <TableHeader>
                    {table.getHeaderGroups().map((headerGroup) => (
                        <TableRow key={headerGroup.id}>
                            {headerGroup.headers.map((header) => (
                                <TableHead key={header.id} className={header.id === 'trainee' ? 'pl-6' : ''}>
                                    {header.isPlaceholder ? null : flexRender(header.column.columnDef.header, header.getContext())}
                                </TableHead>
                            ))}
                        </TableRow>
                    ))}
                </TableHeader>
                <TableBody>
                    {table.getRowModel().rows.length > 0 ? (
                        table.getRowModel().rows.map((row) => (
                            <TableRow key={row.id}>
                                {row.getVisibleCells().map((cell) => (
                                    <TableCell key={cell.id} className={cell.column.id === 'trainee' ? 'pl-6' : ''}>
                                        {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                    </TableCell>
                                ))}
                            </TableRow>
                        ))
                    ) : (
                        <TableRow>
                            <TableCell colSpan={columns.length} className="h-24 text-center">
                                No trainees found.
                            </TableCell>
                        </TableRow>
                    )}
                </TableBody>
            </Table>
            <Dialog open={grantModalOpen} onOpenChange={setGrantModalOpen}>
                <DialogContent className="gap-6">
                    <DialogHeader>
                        <DialogTitle>Grant Endorsement</DialogTitle>
                        <DialogDescription>
                            Are you sure you want to grant <span className="font-medium">{selectedTraineeForGrant?.name}</span> the{' '}
                            <span className="font-monospace">{course.soloStation}</span> endorsement?
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant={'outline'}
                            disabled={isGrantingEndorsement}
                            onClick={() => {
                                setGrantModalOpen(false);
                                setSelectedTraineeForGrant(null);
                            }}
                        >
                            Cancel
                        </Button>
                        <Button onClick={handleGrantEndorsement} disabled={isGrantingEndorsement} variant="success">
                            {isGrantingEndorsement ? 'Granting...' : 'Grant Endorsement'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
            <SoloModal
                trainee={selectedTraineeForSolo}
                courseId={course.id}
                isOpen={soloModalOpen}
                onClose={() => {
                    setSoloModalOpen(false);
                    setSelectedTraineeForSolo(null);
                }}
            />
            <ProgressModal
                trainee={selectedTraineeForProgress}
                courseId={course.id}
                isOpen={progressModalOpen}
                onClose={() => {
                    setProgressModalOpen(false);
                    setSelectedTraineeForProgress(null);
                }}
            />
        </div>
    );
}