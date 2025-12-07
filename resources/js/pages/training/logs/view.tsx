import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { BreadcrumbItem } from '@/types';
import { 
    Calendar, 
    User, 
    Award, 
    Clock, 
    MapPin, 
    Activity, 
    CheckCircle2, 
    XCircle, 
    Edit, 
    AlertCircle,
    Info
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useState } from 'react';
import { Dialog, DialogContent } from '@/components/ui/dialog';

interface EvaluationCategory {
    name: string;
    label: string;
    description: string;
}

interface TrainingLogShowProps {
    log: {
        id: number;
        session_date: string;
        position: string;
        type: string;
        type_display: string;
        traffic_level: string | null;
        traffic_level_display: string | null;
        traffic_complexity: string | null;
        traffic_complexity_display: string | null;
        runway_configuration: string | null;
        surrounding_stations: string | null;
        session_duration: number | null;
        special_procedures: string | null;
        airspace_restrictions: string | null;
        trainee: {
            id: number;
            name: string;
            vatsim_id: number;
        };
        mentor: {
            id: number;
            name: string;
            vatsim_id: number;
        };
        course: {
            id: number;
            name: string;
            position: string;
            type: string;
        } | null;
        evaluations: {
            [key: string]: {
                rating: number;
                rating_display: string;
                positives: string | null;
                negatives: string | null;
            };
        };
        final_comment: string | null;
        internal_remarks: string | null;
        result: boolean;
        next_step: string | null;
        average_rating: number;
        has_ratings: boolean;
        created_at: string;
        updated_at: string;
    };
    canEdit: boolean;
    canViewInternal: boolean;
    categories: EvaluationCategory[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Training Logs',
        href: route('training-logs.index'),
    },
    {
        title: 'View Log',
        href: '',
    },
];

const getRatingColor = (rating: number): string => {
    switch (rating) {
        case 4:
            return 'bg-green-100 text-green-800 border-green-300 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800';
        case 3:
            return 'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-900/30 dark:text-blue-400 dark:border-blue-800';
        case 2:
            return 'bg-yellow-100 text-yellow-800 border-yellow-300 dark:bg-yellow-900/30 dark:text-yellow-400 dark:border-yellow-800';
        case 1:
            return 'bg-red-100 text-red-800 border-red-300 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800';
        default:
            return 'bg-gray-100 text-gray-800 border-gray-300 dark:bg-gray-900/30 dark:text-gray-400 dark:border-gray-800';
    }
};

const getSessionTypeColor = (type: string): string => {
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

const ImagePreviewModal = ({ src, alt, isOpen, onClose }: { src: string; alt: string; isOpen: boolean; onClose: () => void }) => {
    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="flex h-auto w-auto max-w-none items-center justify-center gap-0 border-none bg-transparent p-0 focus:outline-none [&>button]:hidden">
                <img
                    src={src}
                    alt={alt}
                    className="h-auto max-h-[95vh] min-h-[80vh] w-auto max-w-[95vw] min-w-[80vw] cursor-pointer object-contain focus:outline-none"
                    style={{ borderRadius: '10px' }}
                    onClick={onClose}
                />
            </DialogContent>
        </Dialog>
    );
};

const MarkdownContent = ({ content }: { content: string | null }) => {
    const [previewImage, setPreviewImage] = useState<{ src: string; alt: string } | null>(null);

    if (!content) {
        return <p className="text-sm text-muted-foreground italic">No feedback provided.</p>;
    }

    return (
        <>
            <div
                className="prose prose-sm dark:prose-invert max-w-none"
                dangerouslySetInnerHTML={{ __html: content }}
                onClick={(e) => {
                    const target = e.target as HTMLElement;
                    if (target.tagName === 'IMG') {
                        const img = target as HTMLImageElement;
                        setPreviewImage({ src: img.src, alt: img.alt || 'Training log image' });
                    }
                }}
            />
            {previewImage && (
                <ImagePreviewModal src={previewImage.src} alt={previewImage.alt} isOpen={!!previewImage} onClose={() => setPreviewImage(null)} />
            )}
        </>
    );
};

export default function ViewTrainingLog({ log, canEdit, canViewInternal, categories }: TrainingLogShowProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Training Log - ${log.position}`} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-3xl font-bold">
                            <span className="font-monospace">{log.position}</span> Training Session
                        </h1>
                        {log.course && <p className="mt-1 text-muted-foreground">{log.course.name}</p>}
                    </div>
                    <div className="flex flex-col items-end gap-2">
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge variant="outline" className={getSessionTypeColor(log.type)}>
                                {log.type_display}
                            </Badge>
                            <Badge variant={log.result ? 'default' : 'destructive'} className="flex items-center gap-1">
                                {log.result ? (
                                    <>
                                        <CheckCircle2 className="h-3 w-3" />
                                        Passed
                                    </>
                                ) : (
                                    <>
                                        <XCircle className="h-3 w-3" />
                                        Not Passed
                                    </>
                                )}
                            </Badge>
                            {canEdit && (
                                <Button asChild size="sm" variant="outline">
                                    <Link href={route('training-logs.edit', log.id)}>
                                        <Edit className="mr-2 h-4 w-4" />
                                        Edit Log
                                    </Link>
                                </Button>
                            )}
                        </div>
                        <p className="text-sm text-muted-foreground">{log.session_date}</p>
                    </div>
                </div>

                <div className="gap-6">
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Session Information</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                        <User className="h-4 w-4" />
                                        <span>Trainee</span>
                                    </div>
                                    <p className="mt-1 font-medium">{log.trainee.name}</p>
                                    <p className="text-xs text-muted-foreground">VATSIM: {log.trainee.vatsim_id}</p>
                                </div>

                                <Separator />

                                <div>
                                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                        <Award className="h-4 w-4" />
                                        <span>Mentor</span>
                                    </div>
                                    <p className="mt-1 font-medium">{log.mentor.name}</p>
                                    <p className="text-xs text-muted-foreground">VATSIM: {log.mentor.vatsim_id}</p>
                                </div>

                                <Separator />

                                <div>
                                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                        <MapPin className="h-4 w-4" />
                                        <span>Position</span>
                                    </div>
                                    <p className="mt-1 font-medium">{log.position}</p>
                                </div>

                                <Separator />

                                <div>
                                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                        <Calendar className="h-4 w-4" />
                                        <span>Session Date</span>
                                    </div>
                                    <p className="mt-1 font-medium">{log.session_date}</p>
                                </div>

                                {log.session_duration && (
                                    <>
                                        <Separator />
                                        <div>
                                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                                <Clock className="h-4 w-4" />
                                                <span>Duration</span>
                                            </div>
                                            <p className="mt-1 font-medium">{log.session_duration} minutes</p>
                                        </div>
                                    </>
                                )}

                                {log.next_step && (
                                    <>
                                        <Separator />
                                        <div>
                                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                                <Activity className="h-4 w-4" />
                                                <span>Next Step</span>
                                            </div>
                                            <p className="mt-1 font-medium">{log.next_step}</p>
                                        </div>
                                    </>
                                )}

                                {(log.traffic_level ||
                                    log.traffic_complexity ||
                                    log.runway_configuration ||
                                    log.surrounding_stations ||
                                    log.special_procedures ||
                                    log.airspace_restrictions) && (
                                    <>
                                        <Separator className="my-4" />
                                        <div>
                                            <h3 className="mb-3 text-sm font-semibold">Additional Details</h3>

                                            {log.traffic_level && (
                                                <div className="mb-3">
                                                    <p className="text-xs text-muted-foreground">Traffic Level</p>
                                                    <p className="text-sm">{log.traffic_level_display}</p>
                                                </div>
                                            )}

                                            {log.traffic_complexity && (
                                                <div className="mb-3">
                                                    <p className="text-xs text-muted-foreground">Traffic Complexity</p>
                                                    <p className="text-sm">{log.traffic_complexity_display}</p>
                                                </div>
                                            )}

                                            {log.runway_configuration && (
                                                <div className="mb-3">
                                                    <p className="text-xs text-muted-foreground">Runway Configuration</p>
                                                    <p className="text-sm">{log.runway_configuration}</p>
                                                </div>
                                            )}

                                            {log.surrounding_stations && (
                                                <div className="mb-3">
                                                    <p className="text-xs text-muted-foreground">Surrounding Stations</p>
                                                    <p className="text-sm">{log.surrounding_stations}</p>
                                                </div>
                                            )}

                                            {log.special_procedures && (
                                                <div className="mb-3">
                                                    <p className="text-xs text-muted-foreground">Special Procedures</p>
                                                    <div className="text-sm">
                                                        <MarkdownContent content={log.special_procedures} />
                                                    </div>
                                                </div>
                                            )}

                                            {log.airspace_restrictions && (
                                                <div className="mb-3">
                                                    <p className="text-xs text-muted-foreground">Airspace Restrictions</p>
                                                    <div className="text-sm">
                                                        <MarkdownContent content={log.airspace_restrictions} />
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </>
                                )}
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader>
                                <CardTitle>Final Comments</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <MarkdownContent content={log.final_comment} />

                                {canViewInternal && log.internal_remarks && (
                                    <>
                                        <Separator className="my-6" />
                                        <div>
                                            <div className="mb-2 flex items-center gap-2">
                                                <h3 className="text-lg font-semibold">Internal Remarks</h3>
                                                <Badge variant="secondary" className="text-xs">
                                                    Private
                                                </Badge>
                                            </div>
                                            <div className="rounded-lg bg-muted/50 p-4">
                                                <MarkdownContent content={log.internal_remarks} />
                                            </div>
                                        </div>
                                    </>
                                )}
                            </CardContent>
                        </Card>

                        <div>
                            <h2 className="mb-4 text-2xl font-bold">Evaluation Categories</h2>
                            <div className="grid gap-4 md:grid-cols-2">
                                {categories.map((category) => {
                                    const evaluation = log.evaluations[category.name];
                                    if (!evaluation) return null;

                                    return (
                                        <Card key={category.name} className="gap-0 overflow-hidden">
                                            <CardHeader className="pb-3">
                                                <div className="flex items-start justify-between">
                                                    <div className="flex items-center gap-2">
                                                        <CardTitle className="text-base">{category.label}</CardTitle>
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Info className="h-4 w-4 text-muted-foreground" />
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                <p className="max-w-xs">{category.description}</p>
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </div>
                                                    <Badge variant="outline" className={cn('text-xs', getRatingColor(evaluation.rating))}>
                                                        {evaluation.rating_display}
                                                    </Badge>
                                                </div>
                                            </CardHeader>
                                            <CardContent>
                                                {evaluation.positives || evaluation.negatives ? (
                                                    <div className="space-y-4">
                                                        {evaluation.positives && (
                                                            <div>
                                                                <h4 className="mb-2 flex items-center gap-2 text-sm font-medium text-green-700 dark:text-green-400">
                                                                    <CheckCircle2 className="h-4 w-4" />
                                                                    Strengths
                                                                </h4>
                                                                <MarkdownContent content={evaluation.positives} />
                                                            </div>
                                                        )}

                                                        {evaluation.negatives && (
                                                            <div>
                                                                <h4 className="mb-2 flex items-center gap-2 text-sm font-medium text-amber-700 dark:text-amber-400">
                                                                    <AlertCircle className="h-4 w-4" />
                                                                    Areas for Improvement
                                                                </h4>
                                                                <MarkdownContent content={evaluation.negatives} />
                                                            </div>
                                                        )}
                                                    </div>
                                                ) : (
                                                    <p className="text-sm text-muted-foreground italic">No feedback provided.</p>
                                                )}
                                            </CardContent>
                                        </Card>
                                    );
                                })}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <style
                dangerouslySetInnerHTML={{
                    __html: `
                    .prose {
                        max-width: 100%;
                    }
                    .prose p {
                        margin-bottom: 0.5rem;
                    }
                    .prose ul, .prose ol {
                        margin-top: 0.5rem;
                        margin-bottom: 0.5rem;
                        padding-left: 1.5rem;
                    }
                    .prose ul {
                        list-style-type: disc;
                    }
                    .prose ol {
                        list-style-type: decimal;
                    }
                    .prose li {
                        margin-top: 0.25rem;
                        margin-bottom: 0.25rem;
                    }
                    .prose h1, .prose h2, .prose h3 {
                        margin-top: 1rem;
                        margin-bottom: 0.5rem;
                        font-weight: 600;
                    }
                    .prose h1 {
                        font-size: 1.5rem;
                    }
                    .prose h2 {
                        font-size: 1.25rem;
                    }
                    .prose h3 {
                        font-size: 1.125rem;
                    }
                    .prose a {
                        color: rgb(59 130 246);
                        text-decoration: underline;
                    }
                    .prose img {
                        border-radius: 0.375rem;
                        max-width: 100%;
                        max-height: 300px;
                        width: auto;
                        height: auto;
                        object-fit: contain;
                        margin: 0.5rem 0;
                        cursor: pointer;
                        transition: opacity 0.2s;
                    }
                    .prose img:hover {
                        opacity: 0.8;
                    }
                    .prose code {
                        background-color: rgba(0, 0, 0, 0.05);
                        padding: 0.125rem 0.25rem;
                        border-radius: 0.25rem;
                        font-size: 0.875em;
                    }
                    .prose pre {
                        background-color: rgba(0, 0, 0, 0.05);
                        padding: 1rem;
                        border-radius: 0.375rem;
                        overflow-x: auto;
                        margin: 0.5rem 0;
                    }
                    .prose pre code {
                        background-color: transparent;
                        padding: 0;
                    }
                    .prose blockquote {
                        border-left: 4px solid rgba(0, 0, 0, 0.1);
                        padding-left: 1rem;
                        font-style: italic;
                        margin: 0.5rem 0;
                    }
                    .dark .prose code {
                        background-color: rgba(255, 255, 255, 0.1);
                    }
                    .dark .prose pre {
                        background-color: rgba(255, 255, 255, 0.05);
                    }
                    .dark .prose blockquote {
                        border-left-color: rgba(255, 255, 255, 0.2);
                    }
                `,
                }}
            />
        </AppLayout>
    );
}