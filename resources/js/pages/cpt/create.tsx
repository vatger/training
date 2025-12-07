import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Head, router, useForm } from '@inertiajs/react';
import { BreadcrumbItem } from '@/types';
import { useEffect, useState } from 'react';
import axios from 'axios';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'CPT Management',
        href: route('cpt.index'),
    },
    {
        title: 'Schedule CPT',
        href: route('cpt.create'),
    },
];

interface Course {
    id: number;
    name: string;
    solo_station: string;
    position: string;
}

interface User {
    id: number;
    name: string;
    vatsim_id?: number;
}

interface PageProps {
    courses: Course[];
}

interface CourseData {
    examiners: User[];
    mentors: User[];
    trainees: User[];
}

export default function CptCreate({ courses }: PageProps) {
    const d = new Date()
    d.setHours(20,0,0,0);
    const defaultDateFormatted =
  d.getFullYear() +
  '-' + String(d.getMonth() + 1).padStart(2, '0') +
  '-' + String(d.getDate()).padStart(2, '0') +
  'T' + String(d.getHours()).padStart(2, '0') +
  ':' + String(d.getMinutes()).padStart(2, '0');
    const { data, setData, post, processing, errors } = useForm({
        course_id: '',
        trainee_id: '',
        date: defaultDateFormatted,
        examiner_id: '',
        local_id: '',
    });

    const [courseData, setCourseData] = useState<CourseData>({
        examiners: [],
        mentors: [],
        trainees: [],
    });
    const [loadingCourseData, setLoadingCourseData] = useState(false);

    const fetchCourseData = async (courseId: string, date: string) => {
        if (!courseId || !date) return;

        setLoadingCourseData(true);
        try {
            const response = await axios.get(route('cpt.course-data'), {
                params: { course_id: courseId, date },
            });
            setCourseData(response.data);
        } catch (error) {
            console.error('Error fetching course data:', error);
        } finally {
            setLoadingCourseData(false);
        }
    };

    useEffect(() => {
        if (data.course_id && data.date) {
            fetchCourseData(data.course_id, data.date);
        }
    }, [data.course_id, data.date]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('cpt.store'));
    };

    const getMinDateTime = () => {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        return now.toISOString().slice(0, 16);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Schedule CPT" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <Card>
                            <CardHeader>
                            <h2 className="text-xl font-semibold">CPT Details</h2>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleSubmit} className="space-y-6">
                                    <div className="space-y-2">
                                        <Label htmlFor="course_id">
                                            Course <span className="text-red-600">*</span>
                                        </Label>
                                        <Select value={data.course_id} onValueChange={(value) => setData('course_id', value)}>
                                            <SelectTrigger id="course_id">
                                                <SelectValue placeholder="-- Select Course --" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {courses.map((course) => (
                                                    <SelectItem key={course.id} value={course.id.toString()}>
                                                        {course.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.course_id && <p className="text-sm text-red-600">{errors.course_id}</p>}
                                        <p className="text-xs text-muted-foreground">Select the course for which this CPT will be conducted.</p>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="date">
                                            Date & Time <span className="text-red-600">*</span>
                                        </Label>
                                        <Input
                                            id="date"
                                            type="datetime-local"
                                            min={getMinDateTime()}
                                            value={data.date}
                                            onChange={(e) => setData('date', e.target.value)}
                                            required
                                        />
                                        {errors.date && <p className="text-sm text-red-600">{errors.date}</p>}
                                        <p className="text-xs text-muted-foreground">Local time (LCL) for the CPT session.</p>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="trainee_id">
                                            Trainee <span className="text-red-600">*</span>
                                        </Label>
                                        <Select
                                            value={data.trainee_id}
                                            onValueChange={(value) => setData('trainee_id', value)}
                                            disabled={!data.course_id || loadingCourseData}
                                        >
                                            <SelectTrigger id="trainee_id">
                                                <SelectValue placeholder="-- Select Trainee --" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {courseData.trainees.map((trainee) => (
                                                    <SelectItem key={trainee.id} value={trainee.id.toString()}>
                                                        {trainee.name} {trainee.vatsim_id ? `- ${trainee.vatsim_id}` : ''}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.trainee_id && <p className="text-sm text-red-600">{errors.trainee_id}</p>}
                                        <p className="text-xs text-muted-foreground">Select the trainee who will take this CPT.</p>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="examiner_id">
                                            Examiner <span className="text-muted-foreground">(Optional)</span>
                                        </Label>
                                        <div className="flex gap-2">
                                            <Select
                                                value={data.examiner_id}
                                                onValueChange={(value) => setData('examiner_id', value)}
                                                disabled={!data.course_id || loadingCourseData}
                                            >
                                                <SelectTrigger id="examiner_id">
                                                    <SelectValue placeholder="-- Select Examiner (Optional) --" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {courseData.examiners.map((examiner) => (
                                                        <SelectItem key={examiner.id} value={examiner.id.toString()}>
                                                            {examiner.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            {data.examiner_id && (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="icon"
                                                    onClick={() => setData('examiner_id', '')}
                                                >
                                                    ×
                                                </Button>
                                            )}
                                        </div>
                                        {errors.examiner_id && <p className="text-sm text-red-600">{errors.examiner_id}</p>}
                                        <p className="text-xs text-muted-foreground">
                                            You can assign an examiner now or allow one to sign up later.
                                        </p>
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="local_id">
                                            Local Mentor <span className="text-muted-foreground">(Optional)</span>
                                        </Label>
                                        <div className="flex gap-2">
                                            <Select
                                                value={data.local_id}
                                                onValueChange={(value) => setData('local_id', value)}
                                                disabled={!data.course_id || loadingCourseData}
                                            >
                                                <SelectTrigger id="local_id">
                                                    <SelectValue placeholder="-- Select Local Mentor (Optional) --" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {courseData.mentors.map((mentor) => (
                                                        <SelectItem key={mentor.id} value={mentor.id.toString()}>
                                                            {mentor.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            {data.local_id && (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="icon"
                                                    onClick={() => setData('local_id', '')}
                                                >
                                                    ×
                                                </Button>
                                            )}
                                        </div>
                                        {errors.local_id && <p className="text-sm text-red-600">{errors.local_id}</p>}
                                        <p className="text-xs text-muted-foreground">Local mentor who will assist during the CPT session.</p>
                                    </div>

                                    <div className="flex justify-end gap-3 border-t pt-6">
                                        <Button type="button" variant="outline" onClick={() => router.visit(route('cpt.index'))}>
                                            Cancel
                                        </Button>
                                        <Button type="submit" disabled={processing}>
                                            {processing ? 'Scheduling...' : 'Schedule CPT'}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}