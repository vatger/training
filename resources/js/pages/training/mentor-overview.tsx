import { CourseDetail } from '@/components/overview/course-detail';
import { CourseFilter } from '@/components/overview/course-filter';
import { RemarkDialog } from '@/components/overview/remark-dialog';
import { ClaimConfirmDialog, AssignDialog } from '@/components/overview/claim-dialogs';
import { StatisticsCards } from '@/components/overview/statistics-cards';
import { useMentorStorage } from '@/hooks/use-mentor-storage';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { MentorCourse, MentorStatistics, Trainee } from '@/types/mentor';
import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import axios from 'axios';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Mentor Overview',
        href: route('overview.index'),
    },
];

interface Props {
    courses: MentorCourse[];
    statistics: MentorStatistics;
    initialCourseId?: number;
}

export default function MentorOverview({ courses: initialCourses, statistics, initialCourseId }: Props) {
    const [courses, setCourses] = useState<MentorCourse[]>(initialCourses);
    const [loadingCourses, setLoadingCourses] = useState<Set<number>>(new Set());

    const { activeCategory, selectedCourse, setActiveCategory, setSelectedCourse, isInitialized } = useMentorStorage(courses);

    const [selectedTrainee, setSelectedTrainee] = useState<Trainee | null>(null);
    const [isRemarkDialogOpen, setIsRemarkDialogOpen] = useState(false);
    const [isClaimDialogOpen, setIsClaimDialogOpen] = useState(false);
    const [isAssignDialogOpen, setIsAssignDialogOpen] = useState(false);

    // Update courses state when initialCourses changes
    useEffect(() => {
        console.log('Initial courses received:', {
            count: initialCourses.length,
            loadedCourses: initialCourses.filter((c) => c.loaded).map((c) => ({ id: c.id, name: c.name, trainees: c.trainees?.length || 0 })),
            initialCourseId,
        });
        setCourses(initialCourses);
    }, [initialCourses]);

    const filteredCourses = courses.filter((course) => {
        if (activeCategory === 'EDMT_FAM') {
            return course.type === 'EDMT' || course.type === 'FAM';
        }
        return course.type === activeCategory;
    });

    const loadCourseData = async (courseId: number) => {
        if (loadingCourses.has(courseId)) {
            console.log('Already loading course:', courseId);
            return;
        }

        const course = courses.find((c) => c.id === courseId);
        if (course?.loaded) {
            console.log('Course already loaded:', courseId, 'trainees:', course.trainees?.length || 0);
            return;
        }

        console.log('Loading course data:', courseId);
        setLoadingCourses((prev) => new Set(prev).add(courseId));

        try {
            const response = await axios.get(route('overview.course.trainees', { courseId }));
            const courseData = response.data;

            console.log('Course data loaded:', courseData.id, 'trainees:', courseData.trainees?.length || 0);

            setCourses((prevCourses) => {
                const updated = prevCourses.map((c) => (c.id === courseId ? { ...courseData, loaded: true } : c));
                return updated;
            });
        } catch (error) {
            console.error('Failed to load course data:', error);
        } finally {
            setLoadingCourses((prev) => {
                const next = new Set(prev);
                next.delete(courseId);
                return next;
            });
        }
    };

    useEffect(() => {
        if (!isInitialized) return;

        if (filteredCourses.length > 0) {
            if (!selectedCourse || !filteredCourses.find((c) => c.id === selectedCourse.id)) {
                let newSelectedCourse: MentorCourse | undefined;

                if (initialCourseId) {
                    newSelectedCourse = filteredCourses.find((c) => c.id === initialCourseId);
                }

                if (!newSelectedCourse) {
                    newSelectedCourse = filteredCourses.find((c) => c.loaded === true);
                }

                if (!newSelectedCourse) {
                    newSelectedCourse = filteredCourses[0];
                }

                console.log(
                    'Selecting course:',
                    newSelectedCourse.id,
                    'loaded:',
                    newSelectedCourse.loaded,
                    'trainees:',
                    newSelectedCourse.trainees?.length || 0,
                );

                setSelectedCourse(newSelectedCourse);

                if (!newSelectedCourse.loaded) {
                    console.log('Course not loaded, fetching data for:', newSelectedCourse.id);
                    loadCourseData(newSelectedCourse.id);
                }
            } else if (selectedCourse && !selectedCourse.loaded) {
                console.log('Selected course not loaded, fetching data for:', selectedCourse.id);
                loadCourseData(selectedCourse.id);
            }
        } else {
            setSelectedCourse(null);
        }
    }, [activeCategory, filteredCourses.length, isInitialized, initialCourseId]);

    const handleCourseSelect = async (course: MentorCourse) => {
        console.log('Course selected:', course.id, 'loaded:', course.loaded, 'trainees:', course.trainees?.length || 0);
        setSelectedCourse(course);
        if (!course.loaded) {
            await loadCourseData(course.id);
        }
    };

    const handleRemarkClick = (trainee: Trainee) => {
        setSelectedTrainee(trainee);
        setIsRemarkDialogOpen(true);
    };

    const handleClaimClick = (trainee: Trainee) => {
        setSelectedTrainee(trainee);
        setIsClaimDialogOpen(true);
    };

    const handleAssignClick = (trainee: Trainee) => {
        setSelectedTrainee(trainee);
        setIsAssignDialogOpen(true);
    };

    const handleRemarkClose = () => {
        setIsRemarkDialogOpen(false);
        setSelectedTrainee(null);
    };

    const handleClaimClose = () => {
        setIsClaimDialogOpen(false);
        setSelectedTrainee(null);
    };

    const handleAssignClose = () => {
        setIsAssignDialogOpen(false);
        setSelectedTrainee(null);
    };

    // Always get the latest course data from the courses array
    const currentCourse = selectedCourse ? courses.find((c) => c.id === selectedCourse.id) || selectedCourse : null;

    console.log('Render state:', {
        selectedCourseId: selectedCourse?.id,
        currentCourseId: currentCourse?.id,
        currentCourseLoaded: currentCourse?.loaded,
        currentCourseTrainees: currentCourse?.trainees?.length || 0,
        isLoading: currentCourse ? loadingCourses.has(currentCourse.id) : false,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Mentor Overview" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <StatisticsCards statistics={statistics} />

                <CourseFilter
                    courses={courses}
                    activeCategory={activeCategory}
                    selectedCourse={currentCourse}
                    onCategoryChange={setActiveCategory}
                    onCourseSelect={handleCourseSelect}
                />

                {currentCourse && (
                    <CourseDetail
                        course={currentCourse}
                        onRemarkClick={handleRemarkClick}
                        onClaimClick={handleClaimClick}
                        onAssignClick={handleAssignClick}
                        isLoading={loadingCourses.has(currentCourse.id)}
                    />
                )}

                <RemarkDialog
                    trainee={selectedTrainee}
                    courseId={currentCourse?.id || null}
                    isOpen={isRemarkDialogOpen}
                    onClose={handleRemarkClose}
                />

                <ClaimConfirmDialog
                    trainee={selectedTrainee}
                    courseId={currentCourse?.id || null}
                    isOpen={isClaimDialogOpen}
                    onClose={handleClaimClose}
                />

                <AssignDialog
                    trainee={selectedTrainee}
                    courseId={currentCourse?.id || null}
                    isOpen={isAssignDialogOpen}
                    onClose={handleAssignClose}
                />
            </div>
        </AppLayout>
    );
}