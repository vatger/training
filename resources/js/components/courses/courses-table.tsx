import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { cn } from '@/lib/utils';
import { Course } from '@/pages/training/courses';
import { ArrowUpDown, ArrowUp, ArrowDown, Clock, MapPin } from 'lucide-react';
import { useState, useMemo, useEffect } from 'react';
import WaitingListButton from './waiting-list-button';

interface SortableCoursesTableProps {
    courses: Course[];
    onCourseUpdate?: (courseId: number, updates: Partial<Course>) => void;
    userHasActiveRtgCourse?: boolean;
}

type SortField = 'name' | 'airport_name' | 'type' | 'position' | 'rating' | 'mentor_group' | 'waiting_list_position';
type SortDirection = 'asc' | 'desc';

const getTypeColor = (type: string) => {
    switch (type) {
        case 'RTG':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300';
        case 'EDMT':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300';
        case 'FAM':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300';
        case 'GST':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300';
        case 'RST':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300';
    }
};

export default function SortableCoursesTable({ courses: initialCourses, onCourseUpdate, userHasActiveRtgCourse = false }: SortableCoursesTableProps) {
    const [courses, setCourses] = useState(initialCourses);
    const [sortField, setSortField] = useState<SortField>('name');
    const [sortDirection, setSortDirection] = useState<SortDirection>('asc');

    // Update local state when props change
    useEffect(() => {
        setCourses(initialCourses);
    }, [initialCourses]);

    // Handle course updates from the button component
    const handleCourseUpdate = (courseId: number, updates: Partial<Course>) => {
        setCourses((prev) => prev.map((c) => (c.id === courseId ? { ...c, ...updates } : c)));
        onCourseUpdate?.(courseId, updates);
    };

    const handleSort = (field: SortField) => {
        if (sortField === field) {
            setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
        } else {
            setSortField(field);
            setSortDirection('asc');
        }
    };

    const sortedCourses = useMemo(() => {
        return [...courses].sort((a, b) => {
            let aValue;
            let bValue;

            switch (sortField) {
                case 'name':
                    aValue = a.name.toLowerCase();
                    bValue = b.name.toLowerCase();
                    break;
                case 'airport_name':
                    aValue = a.airport_name.toLowerCase();
                    bValue = b.airport_name.toLowerCase();
                    break;
                case 'type':
                    aValue = a.type;
                    bValue = b.type;
                    break;
                case 'position': {
                    const posOrder = { GND: 1, TWR: 2, APP: 3, CTR: 4 };
                    aValue = posOrder[a.position as keyof typeof posOrder] || 99;
                    bValue = posOrder[b.position as keyof typeof posOrder] || 99;
                    break;
                }
                case 'rating':
                    aValue = a.min_rating;
                    bValue = b.min_rating;
                    break;
                case 'mentor_group':
                    aValue = a.mentor_group?.toLowerCase() || '';
                    bValue = b.mentor_group?.toLowerCase() || '';
                    break;
                case 'waiting_list_position':
                    aValue = a.is_on_waiting_list ? a.waiting_list_position || 999 : 999;
                    bValue = b.is_on_waiting_list ? b.waiting_list_position || 999 : 999;
                    break;
                default:
                    aValue = a.name.toLowerCase();
                    bValue = b.name.toLowerCase();
            }

            if (typeof aValue === 'string' && typeof bValue === 'string') {
                return sortDirection === 'asc' ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
            }

            if (sortDirection === 'asc') {
                return aValue < bValue ? -1 : aValue > bValue ? 1 : 0;
            } else {
                return aValue > bValue ? -1 : aValue < bValue ? 1 : 0;
            }
        });
    }, [courses, sortField, sortDirection]);

    const SortableHeader = ({ field, children }: { field: SortField; children: React.ReactNode }) => (
        <TableHead className="cursor-pointer select-none hover:bg-muted/50" onClick={() => handleSort(field)}>
            <div className="flex items-center gap-2">
                {children}
                {sortField === field ? (
                    sortDirection === 'asc' ? (
                        <ArrowUp className="h-4 w-4" />
                    ) : (
                        <ArrowDown className="h-4 w-4" />
                    )
                ) : (
                    <ArrowUpDown className="h-4 w-4 opacity-50" />
                )}
            </div>
        </TableHead>
    );

    return (
        <div className="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <SortableHeader field="name">Course Name</SortableHeader>
                        <SortableHeader field="airport_name">Airport</SortableHeader>
                        <SortableHeader field="type">Type</SortableHeader>
                        <SortableHeader field="position">Position</SortableHeader>
                        <SortableHeader field="waiting_list_position">Queue Status</SortableHeader>
                        <TableHead>Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {sortedCourses.map((course) => {
                        return (
                            <TableRow
                                key={course.id}
                                className={cn('transition-colors', course.is_on_waiting_list && 'bg-blue-50 dark:bg-blue-950/20')}
                            >
                                <TableCell className="pl-4 font-medium">
                                    <div>
                                        <div className="font-semibold">{course.name}</div>
                                    </div>
                                </TableCell>

                                <TableCell>
                                    <div className="flex items-center gap-2">
                                        <MapPin className="h-4 w-4 text-muted-foreground" />
                                        <div>
                                            <div className="font-medium">{course.airport_name}</div>
                                            <div className="text-sm text-muted-foreground">{course.airport_icao}</div>
                                        </div>
                                    </div>
                                </TableCell>

                                <TableCell>
                                    <Badge className={getTypeColor(course.type)}>{course.type_display}</Badge>
                                </TableCell>

                                <TableCell>
                                    <Badge variant="outline" className={'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300'}>
                                        {course.position_display}
                                    </Badge>
                                </TableCell>

                                <TableCell>
                                    {course.is_on_waiting_list ? (
                                        <div className="flex items-center gap-2 text-blue-600 dark:text-blue-400">
                                            <Clock className="h-4 w-4" />
                                            <div>
                                                <div className="text-sm font-medium">Position #{course.waiting_list_position}</div>
                                                {course.waiting_list_activity !== undefined && course.waiting_list_activity !== null && (
                                                    <div className="text-xs text-muted-foreground">{course.waiting_list_activity}h activity</div>
                                                )}
                                            </div>
                                        </div>
                                    ) : (
                                        <span className="text-sm text-muted-foreground">Not in queue</span>
                                    )}
                                </TableCell>

                                <TableCell className="w-42 pr-4">
                                    <WaitingListButton
                                        course={course}
                                        onCourseUpdate={handleCourseUpdate}
                                        className="w-full"
                                        size="sm"
                                        userHasActiveRtgCourse={userHasActiveRtgCourse}
                                    />
                                </TableCell>
                            </TableRow>
                        );
                    })}
                </TableBody>
            </Table>

            {sortedCourses.length === 0 && (
                <div className="py-8 text-center">
                    <div className="text-muted-foreground">No courses found matching your criteria</div>
                </div>
            )}
        </div>
    );
}