import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Head, router, useForm } from '@inertiajs/react';
import { CheckCircle, Download, Eye, File, Upload, XCircle } from 'lucide-react';
import { BreadcrumbItem } from '@/types';
import { useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'CPT Management',
        href: route('cpt.index'),
    },
    {
        title: 'Upload Log',
        href: '#',
    },
];

interface CptData {
    id: number;
    trainee: { id: number; name: string; vatsim_id: number };
    examiner: { id: number; name: string } | null;
    local: { id: number; name: string } | null;
    course: { id: number; name: string; solo_station: string };
    date: string;
    date_formatted: string;
    confirmed: boolean;
    log_uploaded: boolean;
}

interface LogData {
    id: number;
    file_name: string;
    file_url: string;
    uploaded_at: string;
    uploaded_at_formatted: string;
    uploaded_by: { id: number; name: string };
}

interface PageProps {
    cpt: CptData;
    logs: LogData[];
    can_upload: boolean;
    can_review: boolean;
}

export default function CptUpload({ cpt, logs, can_upload, can_review }: PageProps) {
    const { data, setData, post, processing, reset } = useForm({
        log_file: null as File | null,
    });

    const [dragActive, setDragActive] = useState(false);

    const handleDrag = (e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        if (e.type === 'dragenter' || e.type === 'dragover') {
            setDragActive(true);
        } else if (e.type === 'dragleave') {
            setDragActive(false);
        }
    };

    const handleDrop = (e: React.DragEvent) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(false);

        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
            handleFile(e.dataTransfer.files[0]);
        }
    };

    const handleFile = (file: File) => {
        if (file.type !== 'application/pdf') {
            toast.error('Please select a PDF file only');
            return;
        }

        if (file.size > 10 * 1024 * 1024) {
            toast.error('File size must be less than 10MB');
            return;
        }

        setData('log_file', file);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('cpt.upload.store', cpt.id), {
            forceFormData: true,
            onSuccess: () => {
                toast.success('Log uploaded successfully');
                reset();
            },
            onError: () => {
                toast.error('Failed to upload log');
            },
        });
    };

    const handleGrade = (passed: boolean) => {
        if (confirm(`Are you sure you want to mark this CPT as ${passed ? 'passed' : 'failed'}?`)) {
            router.post(route('cpt.grade', { cpt: cpt.id, result: passed ? 1 : 0 }), {}, {
                preserveScroll: true,
                onSuccess: () => toast.success('CPT graded successfully'),
                onError: () => toast.error('Failed to grade CPT'),
            });
        }
    };

    const formatFileSize = (bytes: number) => {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Upload CPT Log" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <h2 className="text-xl font-semibold">CPT Session Details</h2>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div className="space-y-4">
                                        <div>
                                            <p className="text-sm font-medium text-muted-foreground">Trainee</p>
                                            <div className="mt-1">
                                                <p className="font-medium">{cpt.trainee.name}</p>
                                                <p className="text-sm text-muted-foreground">{cpt.trainee.vatsim_id}</p>
                                            </div>
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium text-muted-foreground">Station</p>
                                            <p className="mt-1 font-medium">{cpt.course.solo_station}</p>
                                        </div>
                                    </div>

                                    <div className="space-y-4">
                                        <div>
                                            <p className="text-sm font-medium text-muted-foreground">Date & Time</p>
                                            <p className="mt-1 font-medium">{cpt.date_formatted} LCL</p>
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium text-muted-foreground">Course</p>
                                            <p className="mt-1 font-medium">{cpt.course.name}</p>
                                        </div>
                                    </div>
                                </div>

                                <div className="mt-6 grid grid-cols-1 gap-6 border-t pt-6 md:grid-cols-2">
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground">Examiner</p>
                                        {cpt.examiner ? (
                                            <p className="mt-1 font-medium">{cpt.examiner.name}</p>
                                        ) : (
                                            <p className="mt-1 text-sm text-yellow-600">Not assigned</p>
                                        )}
                                    </div>
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground">Local Mentor</p>
                                        {cpt.local ? (
                                            <p className="mt-1 font-medium">{cpt.local.name}</p>
                                        ) : (
                                            <p className="mt-1 text-sm text-yellow-600">Not assigned</p>
                                        )}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {can_upload && (!cpt.log_uploaded || !can_review) && (
                            <Card>
                                <div className="border-b p-6">
                                    <h2 className="text-xl font-semibold">Upload Training Log</h2>
                                </div>
                                <CardContent className="p-6">
                                    <form onSubmit={handleSubmit} className="space-y-6">
                                        <div
                                            className={`flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed p-12 transition-colors ${
                                                dragActive
                                                    ? 'border-green-500 bg-green-50 dark:bg-green-900/20'
                                                    : 'border-blue-300 bg-blue-50 hover:bg-blue-100 dark:border-blue-700 dark:bg-blue-900/20'
                                            }`}
                                            onDragEnter={handleDrag}
                                            onDragLeave={handleDrag}
                                            onDragOver={handleDrag}
                                            onDrop={handleDrop}
                                            onClick={() => document.getElementById('file-input')?.click()}
                                        >
                                            <Upload className="mb-4 h-16 w-16 text-blue-600" />
                                            <h3 className="mb-2 text-lg font-medium text-blue-600">Upload CPT Log</h3>
                                            <p className="mb-4 text-center text-sm text-blue-600">
                                                Drag and drop your PDF file here, or click to browse
                                            </p>
                                            <p className="text-xs text-muted-foreground">PDF files only • Maximum size: 10MB</p>
                                            <input
                                                id="file-input"
                                                type="file"
                                                className="hidden"
                                                accept="application/pdf"
                                                onChange={(e) => e.target.files && handleFile(e.target.files[0])}
                                            />
                                        </div>

                                        {data.log_file && (
                                            <div className="rounded-lg border border-green-500 bg-green-50 p-4 dark:bg-green-900/20">
                                                <div className="flex items-center justify-between">
                                                    <div className="flex items-center gap-3">
                                                        <div className="rounded bg-red-100 p-2 dark:bg-red-900">
                                                            <Eye className="h-6 w-6 text-red-600" />
                                                        </div>
                                                        <div>
                                                            <p className="font-medium text-green-600">{data.log_file.name}</p>
                                                            <p className="text-sm text-green-600">{formatFileSize(data.log_file.size)}</p>
                                                        </div>
                                                    </div>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => setData('log_file', null)}
                                                        className="text-green-600"
                                                    >
                                                        Remove
                                                    </Button>
                                                </div>
                                            </div>
                                        )}

                                        {data.log_file && (
                                            <div className="flex justify-end">
                                                <Button type="submit" disabled={processing}>
                                                    <Upload className="mr-2 h-4 w-4" />
                                                    {processing ? 'Uploading...' : 'Upload Log'}
                                                </Button>
                                            </div>
                                        )}
                                    </form>
                                </CardContent>
                            </Card>
                        )}

                        {logs.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <h3 className="font-medium">Uploaded Documents</h3>
                                    <p className="text-sm text-muted-foreground">{logs.length} document{logs.length !== 1 && 's'} uploaded</p>
                                </CardHeader>
                                <CardContent className="divide-y p-0">
                                    {logs.map((log) => (
                                        <div key={log.id} className="p-6">
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-4">
                                                    <div className="rounded bg-red-100 p-3 dark:bg-red-900">
                                                        <File className="h-6 w-6 text-red-600" />
                                                    </div>
                                                    <div>
                                                        <h4 className="font-semibold">{log.file_name}</h4>
                                                        <div className="mt-1 flex items-center gap-4">
                                                            <p className="text-sm text-muted-foreground">
                                                                Uploaded: {log.uploaded_at_formatted}
                                                            </p>
                                                            <Badge variant="secondary">PDF Document</Badge>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="flex gap-2">
                                                    <Button variant="outline" size="sm" asChild>
                                                        <a href={log.file_url} target="_blank" rel="noopener noreferrer">
                                                            <Eye className="mr-1 h-4 w-4" />
                                                            View
                                                        </a>
                                                    </Button>
                                                    <Button size="sm" asChild>
                                                        <a href={log.file_url} download>
                                                            <Download className="mr-1 h-4 w-4" />
                                                            Download
                                                        </a>
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        )}

                        {logs.length > 0 && logs[0] && (
                            <Card>
                                <CardHeader>
                                    <h3 className="font-medium">Document Preview</h3>
                                    <p className="text-sm text-muted-foreground">Preview of the uploaded training log</p>
                                </CardHeader>
                                <CardContent>
                                    <div className="h-[600px] w-full">
                                        <iframe src={logs[0].file_url} className="h-full w-full rounded-lg border-0" title="CPT Training Log Preview" />
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {can_review && logs.length > 0 && (
                        <div className="lg:col-span-1">
                            <Card>
                                <div className="p-6">
                                    <h3 className="text-lg font-semibold">CPT Review</h3>
                                    <p className="mt-2 text-sm text-muted-foreground">Review this CPT and send as pass/fail to EUD.</p>

                                    <div className="mt-6 space-y-3">
                                        <Button className="w-full" variant="default" onClick={() => handleGrade(true)}>
                                            <CheckCircle className="mr-2 h-5 w-5" />
                                            CPT Passed
                                        </Button>

                                        <Button className="w-full" variant="destructive" onClick={() => handleGrade(false)}>
                                            <XCircle className="mr-2 h-5 w-5" />
                                            CPT Failed
                                        </Button>
                                    </div>
                                </div>
                            </Card>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}