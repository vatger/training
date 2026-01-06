import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { 
    Calendar, 
    CheckCircle2, 
    Clock, 
    Users, 
    XCircle,
    UserCheck,
    UserX,
    ListChecks,
    CalendarPlus,
    Trash2,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

interface Module {
    id: number;
    name: string;
    sequence_order: number;
}

interface Participant {
    id: number;
    user_id: number;
    user_name: string;
    user_vatsim_id: string;
    waiting_list_position: number | null;
    attendance: {
        id: number;
        status: 'passed' | 'failed' | 'excused' | 'absent';
        remarks: string | null;
    } | null;
}

interface Session {
    id: number;
    module_name: string;
    module_id: number;
    scheduled_at: string;
    max_trainees: number;
    language: string;
    signups_open: boolean;
    signups_locked: boolean;
    attendance_completed: boolean;
    total_signups: number;
    selected_count: number;
    participants?: Participant[];
}

interface PastSession {
    id: number;
    module_name: string;
    scheduled_at: string;
    max_trainees: number;
    attendance_completed: boolean;
    participants_count: number;
}

interface WaitingListUser {
    id: number;
    user_id: number;
    user_name: string;
    user_vatsim_id: string;
    position: number;
    joined_at: string;
    last_confirmed_at: string;
    needs_confirmation: boolean;
}

interface WaitingList {
    id: number;
    name: string;
    sequence_order: number;
    waiting_count: number;
    users: WaitingListUser[];
}

interface Props {
    modules: Module[];
    upcomingSessions: Session[];
    pastSessions: PastSession[];
    waitingLists: WaitingList[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'S1 Training', href: route('s1.training') },
    { title: 'Mentor Dashboard', href: route('s1.mentor.index') },
];

const attendanceStatuses = [
    { value: 'passed', label: 'Passed', icon: CheckCircle2, color: 'text-green-600', bgColor: 'bg-green-50 dark:bg-green-950' },
    { value: 'failed', label: 'Failed', icon: XCircle, color: 'text-red-600', bgColor: 'bg-red-50 dark:bg-red-950' },
    { value: 'excused', label: 'Excused', icon: UserCheck, color: 'text-blue-600', bgColor: 'bg-blue-50 dark:bg-blue-950' },
    { value: 'absent', label: 'Absent', icon: UserX, color: 'text-orange-600', bgColor: 'bg-orange-50 dark:bg-orange-950' },
];

export default function S1Mentor({ modules, upcomingSessions, pastSessions, waitingLists }: Props) {
    const { flash } = usePage<{ flash: { success?: string; error?: string } }>().props;
    const [createSessionOpen, setCreateSessionOpen] = useState(false);
    const [attendanceDialogOpen, setAttendanceDialogOpen] = useState(false);
    const [selectedSession, setSelectedSession] = useState<Session | null>(null);
    const [attendanceData, setAttendanceData] = useState<Record<number, { status: string; remarks: string }>>({});

    const createSessionForm = useForm({
        module_id: '',
        scheduled_at: '',
        max_trainees: 15,
        language: 'DE',
        notes: '',
    });

    useEffect(() => {
        if (flash.success) {
            toast.success(flash.success);
        }
        if (flash.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    const handleCreateSession = () => {
        createSessionForm.post('/s1/mentor/sessions', {
            preserveScroll: true,
            onSuccess: () => {
                setCreateSessionOpen(false);
                createSessionForm.reset();
            },
        });
    };

    const openAttendanceDialog = (session: Session) => {
        setSelectedSession(session);
        const initialData: Record<number, { status: string; remarks: string }> = {};
        session.participants?.forEach((participant) => {
            initialData[participant.id] = {
                status: participant.attendance?.status || '',
                remarks: participant.attendance?.remarks || '',
            };
        });
        setAttendanceData(initialData);
        setAttendanceDialogOpen(true);
    };

    const handleRecordAttendance = () => {
        if (!selectedSession) return;

        const attendances = Object.entries(attendanceData)
            .filter(([_, data]) => data.status)
            .map(([signupId, data]) => ({
                signup_id: parseInt(signupId),
                status: data.status,
                remarks: data.remarks || null,
            }));

        if (attendances.length === 0) {
            toast.error('Please select attendance status for at least one participant');
            return;
        }

        router.post(
            `/s1/mentor/sessions/${selectedSession.id}/attendance`,
            { attendances },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setAttendanceDialogOpen(false);
                    setSelectedSession(null);
                    setAttendanceData({});
                },
            },
        );
    };

    const updateAttendanceStatus = (signupId: number, status: string) => {
        setAttendanceData((prev) => ({
            ...prev,
            [signupId]: {
                ...prev[signupId],
                status,
            },
        }));
    };

    const updateAttendanceRemarks = (signupId: number, remarks: string) => {
        setAttendanceData((prev) => ({
            ...prev,
            [signupId]: {
                ...prev[signupId],
                remarks,
            },
        }));
    };

    const toggleSignups = (sessionId: number) => {
        router.post(`/s1/mentor/sessions/${sessionId}/toggle-signups`, {}, {
            preserveScroll: true,
        });
    };

    const deleteSession = (sessionId: number) => {
        if (!confirm('Are you sure you want to delete this session?')) return;
        
        router.delete(`/s1/mentor/sessions/${sessionId}`, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="S1 Mentor Dashboard" />

            {/* Create Session Dialog */}
            <Dialog open={createSessionOpen} onOpenChange={setCreateSessionOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Create Training Session</DialogTitle>
                        <DialogDescription>Schedule a new training session for a module</DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                        <div>
                            <Label htmlFor="module">Module</Label>
                            <Select value={createSessionForm.data.module_id} onValueChange={(value) => createSessionForm.setData('module_id', value)}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Select module" />
                                </SelectTrigger>
                                <SelectContent>
                                    {modules.map((module) => (
                                        <SelectItem key={module.id} value={module.id.toString()}>
                                            {module.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label htmlFor="scheduled_at">Date & Time</Label>
                            <Input
                                id="scheduled_at"
                                type="datetime-local"
                                value={createSessionForm.data.scheduled_at}
                                onChange={(e) => createSessionForm.setData('scheduled_at', e.target.value)}
                            />
                        </div>
                        <div>
                            <Label htmlFor="max_trainees">Max Trainees</Label>
                            <Input
                                id="max_trainees"
                                type="number"
                                min="1"
                                max="50"
                                value={createSessionForm.data.max_trainees}
                                onChange={(e) => createSessionForm.setData('max_trainees', parseInt(e.target.value))}
                            />
                        </div>
                        <div>
                            <Label htmlFor="language">Language</Label>
                            <Select value={createSessionForm.data.language} onValueChange={(value) => createSessionForm.setData('language', value)}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="DE">German (DE)</SelectItem>
                                    <SelectItem value="EN">English (EN)</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label htmlFor="notes">Notes (Optional)</Label>
                            <Textarea
                                id="notes"
                                value={createSessionForm.data.notes}
                                onChange={(e) => createSessionForm.setData('notes', e.target.value)}
                                placeholder="Additional notes for this session..."
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setCreateSessionOpen(false)}>
                            Cancel
                        </Button>
                        <Button onClick={handleCreateSession} disabled={createSessionForm.processing}>
                            {createSessionForm.processing ? 'Creating...' : 'Create Session'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Attendance Dialog */}
            <Dialog open={attendanceDialogOpen} onOpenChange={setAttendanceDialogOpen}>
                <DialogContent className="max-h-[80vh] overflow-y-auto ">
                    <DialogHeader>
                        <DialogTitle>Record Attendance</DialogTitle>
                        <DialogDescription>
                            {selectedSession?.module_name} - {selectedSession && new Date(selectedSession.scheduled_at).toLocaleString('de')}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                        <Alert>
                            <ListChecks className="h-4 w-4" />
                            <AlertTitle>Attendance Rules</AlertTitle>
                            <AlertDescription>
                                <ul className="mt-2 space-y-1 text-sm">
                                    <li><strong>Passed:</strong> Module completed successfully</li>
                                    <li><strong>Failed:</strong> Did not pass, loses waiting list position</li>
                                    <li><strong>Excused:</strong> Valid excuse, keeps waiting list position</li>
                                    <li><strong>Absent:</strong> No-show, loses waiting list position</li>
                                </ul>
                            </AlertDescription>
                        </Alert>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Student</TableHead>
                                    <TableHead>VATSIM ID</TableHead>
                                    <TableHead>Position</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Remarks</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {selectedSession?.participants?.map((participant) => (
                                    <TableRow key={participant.id}>
                                        <TableCell className="font-medium">{participant.user_name}</TableCell>
                                        <TableCell>{participant.user_vatsim_id}</TableCell>
                                        <TableCell>#{participant.waiting_list_position || 'N/A'}</TableCell>
                                        <TableCell>
                                            <Select
                                                value={attendanceData[participant.id]?.status || ''}
                                                onValueChange={(value) => updateAttendanceStatus(participant.id, value)}
                                            >
                                                <SelectTrigger className="w-35">
                                                    <SelectValue placeholder="Select status" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {attendanceStatuses.map((status) => (
                                                        <SelectItem key={status.value} value={status.value}>
                                                            <div className="flex items-center gap-2">
                                                                <status.icon className={`h-4 w-4 ${status.color}`} />
                                                                {status.label}
                                                            </div>
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                placeholder="Optional remarks..."
                                                value={attendanceData[participant.id]?.remarks || ''}
                                                onChange={(e) => updateAttendanceRemarks(participant.id, e.target.value)}
                                            />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setAttendanceDialogOpen(false)}>
                            Cancel
                        </Button>
                        <Button onClick={handleRecordAttendance}>
                            Save Attendance
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold">S1 Mentor Dashboard</h1>
                        <p className="mt-1 text-muted-foreground">Manage training sessions and waiting lists</p>
                    </div>
                    <Button onClick={() => setCreateSessionOpen(true)}>
                        <CalendarPlus className="mr-2 h-4 w-4" />
                        Create Session
                    </Button>
                </div>

                <Tabs defaultValue="sessions" className="space-y-4">
                    <TabsList>
                        <TabsTrigger value="sessions">Sessions</TabsTrigger>
                        <TabsTrigger value="waiting-lists">Waiting Lists</TabsTrigger>
                        <TabsTrigger value="past">Past Sessions</TabsTrigger>
                    </TabsList>

                    <TabsContent value="sessions" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Upcoming Sessions</CardTitle>
                                <CardDescription>Manage your scheduled training sessions</CardDescription>
                            </CardHeader>
                            <CardContent>
                                {upcomingSessions.length === 0 ? (
                                    <div className="py-8 text-center text-muted-foreground">
                                        No upcoming sessions scheduled
                                    </div>
                                ) : (
                                    <div className="space-y-4">
                                        {upcomingSessions.map((session) => (
                                            <Card key={session.id}>
                                                <CardHeader>
                                                    <div className="flex items-start justify-between">
                                                        <div>
                                                            <CardTitle className="text-lg">{session.module_name}</CardTitle>
                                                            <CardDescription>
                                                                <div className="mt-2 flex items-center gap-4 text-sm">
                                                                    <span className="flex items-center gap-1">
                                                                        <Calendar className="h-4 w-4" />
                                                                        {new Date(session.scheduled_at).toLocaleString('de')}
                                                                    </span>
                                                                    <span className="flex items-center gap-1">
                                                                        <Users className="h-4 w-4" />
                                                                        {session.selected_count} / {session.max_trainees}
                                                                    </span>
                                                                    <Badge variant="outline">{session.language}</Badge>
                                                                </div>
                                                            </CardDescription>
                                                        </div>
                                                        <div className="flex gap-2">
                                                            {session.signups_locked && (
                                                                <Badge variant="secondary">Locked</Badge>
                                                            )}
                                                            {session.attendance_completed ? (
                                                                <Badge variant="default" className="bg-green-600">
                                                                    <CheckCircle2 className="mr-1 h-3 w-3" />
                                                                    Complete
                                                                </Badge>
                                                            ) : (
                                                                <Badge variant={session.signups_open ? 'default' : 'outline'}>
                                                                    {session.signups_open ? 'Open' : 'Closed'}
                                                                </Badge>
                                                            )}
                                                        </div>
                                                    </div>
                                                </CardHeader>
                                                <CardContent className="space-y-3">
                                                    {session.participants && session.participants.length > 0 && (
                                                        <div>
                                                            <h4 className="mb-2 text-sm font-medium">Selected Participants</h4>
                                                            <div className="space-y-1">
                                                                {session.participants.map((participant) => (
                                                                    <div
                                                                        key={participant.id}
                                                                        className="flex items-center justify-between rounded-lg border p-2 text-sm"
                                                                    >
                                                                        <div className="flex items-center gap-3">
                                                                            {participant.attendance ? (
                                                                                (() => {
                                                                                    const status = attendanceStatuses.find(
                                                                                        (s) => s.value === participant.attendance?.status
                                                                                    );
                                                                                    return status ? (
                                                                                        <status.icon className={`h-4 w-4 ${status.color}`} />
                                                                                    ) : null;
                                                                                })()
                                                                            ) : (
                                                                                <Clock className="h-4 w-4 text-muted-foreground" />
                                                                            )}
                                                                            <span className="font-medium">{participant.user_name}</span>
                                                                            <span className="text-muted-foreground">
                                                                                ({participant.user_vatsim_id})
                                                                            </span>
                                                                        </div>
                                                                        {participant.attendance && (
                                                                            <Badge
                                                                                variant={
                                                                                    participant.attendance.status === 'passed'
                                                                                        ? 'default'
                                                                                        : 'secondary'
                                                                                }
                                                                                className={
                                                                                    attendanceStatuses.find(
                                                                                        (s) => s.value === participant.attendance?.status
                                                                                    )?.bgColor
                                                                                }
                                                                            >
                                                                                {attendanceStatuses.find(
                                                                                    (s) => s.value === participant.attendance?.status
                                                                                )?.label}
                                                                            </Badge>
                                                                        )}
                                                                    </div>
                                                                ))}
                                                            </div>
                                                        </div>
                                                    )}

                                                    <div className="flex gap-2">
                                                        {!session.attendance_completed && session.selected_count > 0 && (
                                                            <Button
                                                                onClick={() => openAttendanceDialog(session)}
                                                                variant="default"
                                                                size="sm"
                                                            >
                                                                <ListChecks className="mr-2 h-4 w-4" />
                                                                {session.participants?.some((p) => p.attendance)
                                                                    ? 'Update Attendance'
                                                                    : 'Record Attendance'}
                                                            </Button>
                                                        )}
                                                        {!session.attendance_completed && (
                                                            <>
                                                                <Button
                                                                    onClick={() => toggleSignups(session.id)}
                                                                    variant="outline"
                                                                    size="sm"
                                                                >
                                                                    {session.signups_open ? 'Close Signups' : 'Open Signups'}
                                                                </Button>
                                                                <Button
                                                                    onClick={() => deleteSession(session.id)}
                                                                    variant="outline"
                                                                    size="sm"
                                                                    className="text-red-600"
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </Button>
                                                            </>
                                                        )}
                                                    </div>
                                                </CardContent>
                                            </Card>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="waiting-lists" className="space-y-4">
                        {waitingLists.map((waitingList) => (
                            <Card key={waitingList.id}>
                                <CardHeader>
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <CardTitle>{waitingList.name}</CardTitle>
                                            <CardDescription>{waitingList.waiting_count} users waiting</CardDescription>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    {waitingList.users.length === 0 ? (
                                        <div className="py-4 text-center text-sm text-muted-foreground">
                                            No users on waiting list
                                        </div>
                                    ) : (
                                        <Table>
                                            <TableHeader>
                                                <TableRow>
                                                    <TableHead>Position</TableHead>
                                                    <TableHead>Name</TableHead>
                                                    <TableHead>VATSIM ID</TableHead>
                                                    <TableHead>Joined</TableHead>
                                                    <TableHead>Last Confirmed</TableHead>
                                                    <TableHead>Status</TableHead>
                                                </TableRow>
                                            </TableHeader>
                                            <TableBody>
                                                {waitingList.users.map((user) => (
                                                    <TableRow key={user.id}>
                                                        <TableCell className="font-medium">#{user.position}</TableCell>
                                                        <TableCell>{user.user_name}</TableCell>
                                                        <TableCell>{user.user_vatsim_id}</TableCell>
                                                        <TableCell>{new Date(user.joined_at).toLocaleDateString('de')}</TableCell>
                                                        <TableCell>
                                                            {new Date(user.last_confirmed_at).toLocaleDateString('de')}
                                                        </TableCell>
                                                        <TableCell>
                                                            {user.needs_confirmation ? (
                                                                <Badge variant="destructive">Needs Confirmation</Badge>
                                                            ) : (
                                                                <Badge variant="outline">Active</Badge>
                                                            )}
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                            </TableBody>
                                        </Table>
                                    )}
                                </CardContent>
                            </Card>
                        ))}
                    </TabsContent>

                    <TabsContent value="past" className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>Past Sessions</CardTitle>
                                <CardDescription>Your previous training sessions</CardDescription>
                            </CardHeader>
                            <CardContent>
                                {pastSessions.length === 0 ? (
                                    <div className="py-8 text-center text-muted-foreground">
                                        No past sessions
                                    </div>
                                ) : (
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Module</TableHead>
                                                <TableHead>Date</TableHead>
                                                <TableHead>Participants</TableHead>
                                                <TableHead>Status</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {pastSessions.map((session) => (
                                                <TableRow key={session.id}>
                                                    <TableCell className="font-medium">{session.module_name}</TableCell>
                                                    <TableCell>{new Date(session.scheduled_at).toLocaleString('de')}</TableCell>
                                                    <TableCell>
                                                        {session.participants_count} / {session.max_trainees}
                                                    </TableCell>
                                                    <TableCell>
                                                        {session.attendance_completed ? (
                                                            <Badge variant="default" className="bg-green-600">
                                                                <CheckCircle2 className="mr-1 h-3 w-3" />
                                                                Complete
                                                            </Badge>
                                                        ) : (
                                                            <Badge variant="outline">Incomplete</Badge>
                                                        )}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                )}
                            </CardContent>
                        </Card>
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}