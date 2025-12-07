import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Head, router } from '@inertiajs/react';
import { Calendar, FileText, Plus, Trash2, Upload, UserCheck, UserPlus } from 'lucide-react';
import { BreadcrumbItem } from '@/types';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'CPT Management',
        href: route('cpt.index'),
    },
];

interface CptTrainee {
    id: number;
    name: string;
    vatsim_id: number;
}

interface CptUser {
    id: number;
    name: string;
    is_current_user: boolean;
}

interface CptCourse {
    id: number;
    name: string;
    solo_station: string;
    position: string;
}

interface Cpt {
    id: number;
    trainee: CptTrainee;
    examiner: CptUser | null;
    local: CptUser | null;
    course: CptCourse;
    date: string;
    date_formatted: string;
    time_formatted: string;
    confirmed: boolean;
    log_uploaded: boolean;
    can_delete: boolean;
    can_view_upload: boolean;
    can_upload: boolean;
    can_join_examiner: boolean;
    can_join_local: boolean;
}

interface Statistics {
    total_cpts: number;
    confirmed_cpts: number;
    pending_cpts: number;
}

interface PageProps {
    cpts: Cpt[];
    statistics: Statistics;
}

export default function CptManagement({ cpts, statistics }: PageProps) {
    const handleJoinExaminer = (cptId: number) => {
        router.post(route('cpt.join-examiner', cptId), {}, {
            preserveScroll: true,
            onSuccess: () => toast.success('Successfully joined as examiner'),
            onError: () => toast.error('Failed to join as examiner'),
        });
    };

    const handleLeaveExaminer = (cptId: number) => {
        router.post(route('cpt.leave-examiner', cptId), {}, {
            preserveScroll: true,
            onSuccess: () => toast.success('Successfully left as examiner'),
            onError: () => toast.error('Failed to leave as examiner'),
        });
    };

    const handleJoinLocal = (cptId: number) => {
        router.post(route('cpt.join-local', cptId), {}, {
            preserveScroll: true,
            onSuccess: () => toast.success('Successfully joined as local mentor'),
            onError: () => toast.error('Failed to join as local mentor'),
        });
    };

    const handleLeaveLocal = (cptId: number) => {
        router.post(route('cpt.leave-local', cptId), {}, {
            preserveScroll: true,
            onSuccess: () => toast.success('Successfully left as local mentor'),
            onError: () => toast.error('Failed to leave as local mentor'),
        });
    };

    const handleDelete = (cptId: number) => {
        if (confirm('Are you sure you want to delete this CPT? This action cannot be undone.')) {
            router.delete(route('cpt.destroy', cptId), {
                preserveScroll: true,
                onSuccess: () => toast.success('CPT deleted successfully'),
                onError: () => toast.error('Failed to delete CPT'),
            });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="CPT Management" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">

                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                <Card className="@container/card">
                        <CardHeader>
                            <CardDescription>Total CPTs</CardDescription>
                            <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                                {statistics.total_cpts}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-sm text-muted-foreground">Currently scheduled exams</CardContent>
                    </Card>

                    <Card className="@container/card">
                        <CardHeader>
                            <CardDescription>Confirmed CPTs</CardDescription>
                            <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                                {statistics.confirmed_cpts}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-sm text-muted-foreground">Ready to proceed</CardContent>
                    </Card>

                    <Card className="@container/card">
                        <CardHeader>
                            <CardDescription>Pending</CardDescription>
                            <CardTitle className="text-2xl font-semibold tabular-nums @[250px]/card:text-3xl">
                                {statistics.pending_cpts}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="text-sm text-muted-foreground">Need examiner/mentor</CardContent>
                    </Card>

                </div>

                <Card>
                    <CardHeader className='flex justify-between items-center'>
                        <div>
                        <h3 className="text-lg font-medium">Scheduled CPTs</h3>
                        <p className="text-sm text-muted-foreground">Manage CPTs and assignments</p>
                        </div>
                        
                        <Button onClick={() => router.visit(route('cpt.create'))}>
                        <Plus className="mr-2 h-4 w-4" />
                        Schedule New CPT
                    </Button>
                    </CardHeader>

                    {cpts.length === 0 ? (
                        <CardContent className="py-12 text-center">
                            <Calendar className="mx-auto h-12 w-12 text-muted-foreground" />
                            <h3 className="mt-2 text-sm font-medium">No CPTs scheduled</h3>
                            <p className="mt-1 text-sm text-muted-foreground">Get started by scheduling your first CPT.</p>
                            <div className="mt-6">
                                <Button onClick={() => router.visit(route('cpt.create'))}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Schedule New CPT
                                </Button>
                            </div>
                        </CardContent>
                    ) : (
                        <CardContent className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Station & Status</TableHead>
                                        <TableHead>Trainee</TableHead>
                                        <TableHead>Date & Time</TableHead>
                                        <TableHead>Examiner</TableHead>
                                        <TableHead>Local Mentor</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {cpts.map((cpt) => (
                                        <TableRow key={cpt.id}>
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    <div className={cn(
                                                        'h-2 w-2 rounded-full',
                                                        cpt.confirmed ? 'bg-green-500' : 'bg-yellow-500'
                                                    )} />
                                                    <div>
                                                        <div className="font-medium">{cpt.course.solo_station}</div>
                                                        <div className={cn(
                                                            'text-xs',
                                                            cpt.confirmed ? 'text-green-600' : 'text-yellow-600'
                                                        )}>
                                                            {cpt.confirmed ? 'Confirmed' : 'Pending'}
                                                        </div>
                                                    </div>
                                                </div>
                                            </TableCell>

                                            <TableCell>
                                                <div>
                                                    <div className="font-medium">{cpt.trainee.name}</div>
                                                    <div className="text-sm text-muted-foreground">{cpt.trainee.vatsim_id}</div>
                                                </div>
                                            </TableCell>

                                            <TableCell>
                                                <div>
                                                    <div className="font-medium">{cpt.date_formatted}</div>
                                                    <div className="text-sm text-muted-foreground">{cpt.time_formatted} LCL</div>
                                                </div>
                                            </TableCell>

                                            <TableCell>
                                                {cpt.examiner ? (
                                                    <div className="flex items-center gap-2">
                                                        <UserCheck className="h-4 w-4 text-green-600" />
                                                        <div>
                                                            <div className="text-sm font-medium">{cpt.examiner.name}</div>
                                                            {cpt.examiner.is_current_user && (
                                                                <Button
                                                                    variant="link"
                                                                    size="sm"
                                                                    className="h-auto p-0 text-xs text-red-600"
                                                                    onClick={() => handleLeaveExaminer(cpt.id)}
                                                                >
                                                                    Cancel Assignment
                                                                </Button>
                                                            )}
                                                        </div>
                                                    </div>
                                                ) : cpt.can_join_examiner ? (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => handleJoinExaminer(cpt.id)}
                                                    >
                                                        <UserPlus className="mr-1 h-4 w-4" />
                                                        Sign Up
                                                    </Button>
                                                ) : (
                                                    <span className="text-sm text-muted-foreground">—</span>
                                                )}
                                            </TableCell>

                                            <TableCell>
                                                {cpt.local ? (
                                                    <div className="flex items-center gap-2">
                                                        <UserCheck className="h-4 w-4 text-blue-600" />
                                                        <div>
                                                            <div className="text-sm font-medium">{cpt.local.name}</div>
                                                            {cpt.local.is_current_user && (
                                                                <Button
                                                                    variant="link"
                                                                    size="sm"
                                                                    className="h-auto p-0 text-xs text-red-600"
                                                                    onClick={() => handleLeaveLocal(cpt.id)}
                                                                >
                                                                    Cancel Assignment
                                                                </Button>
                                                            )}
                                                        </div>
                                                    </div>
                                                ) : cpt.can_join_local ? (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => handleJoinLocal(cpt.id)}
                                                    >
                                                        <UserPlus className="mr-1 h-4 w-4" />
                                                        Sign Up
                                                    </Button>
                                                ) : (
                                                    <span className="text-sm text-muted-foreground">—</span>
                                                )}
                                            </TableCell>

                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    {cpt.can_view_upload && (
                                                        <Button
                                                            variant={cpt.log_uploaded ? 'secondary' : 'default'}
                                                            size="sm"
                                                            onClick={() => router.visit(route('cpt.upload', cpt.id))}
                                                        >
                                                            {cpt.log_uploaded ? (
                                                                <>
                                                                    <FileText className="mr-1 h-4 w-4" />
                                                                    {cpt.can_upload ? 'Manage Log' : 'View Log'}
                                                                </>
                                                            ) : cpt.can_upload ? (
                                                                <>
                                                                    <Upload className="mr-1 h-4 w-4" />
                                                                    Upload Log
                                                                </>
                                                            ) : null}
                                                        </Button>
                                                    )}

                                                    {cpt.can_delete && (
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                            onClick={() => handleDelete(cpt.id)}
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    )}
                </Card>
            </div>
        </AppLayout>
    );
}