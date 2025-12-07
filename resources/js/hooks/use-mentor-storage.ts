import { useEffect, useState } from 'react';
import { MentorCourse } from '@/types/mentor';

const STORAGE_KEY = 'mentor_overview_state';

interface MentorStorageState {
    activeCategory: string;
    selectedCourseId: number | null;
}

/**
 * Custom hook to manage mentor overview state persistence
 * Saves and retrieves the active category and selected course from localStorage
 */
export function useMentorStorage(courses: MentorCourse[]) {
    const [activeCategory, setActiveCategory] = useState<string>('RTG');
    const [selectedCourse, setSelectedCourse] = useState<MentorCourse | null>(null);
    const [isInitialized, setIsInitialized] = useState(false);

    // Load state from localStorage on mount
    useEffect(() => {
        const loadState = () => {
            try {
                const savedState = localStorage.getItem(STORAGE_KEY);
                if (savedState) {
                    const parsedState: MentorStorageState = JSON.parse(savedState);

                    // Restore active category
                    if (parsedState.activeCategory) {
                        setActiveCategory(parsedState.activeCategory);
                    }

                    // FIX: Restore selected course and prefer the one with loaded=true from backend
                    if (parsedState.selectedCourseId) {
                        const savedCourse = courses.find((c) => c.id === parsedState.selectedCourseId);
                        if (savedCourse) {
                            console.log(
                                'Restoring saved course:',
                                savedCourse.id,
                                'loaded:',
                                savedCourse.loaded,
                                'trainees:',
                                savedCourse.trainees?.length || 0,
                            );
                            setSelectedCourse(savedCourse);
                        }
                    } else {
                        // FIX: If no saved course, select the first one that has loaded=true from backend
                        const loadedCourse = courses.find((c) => c.loaded === true);
                        if (loadedCourse) {
                            console.log('No saved course, using loaded course:', loadedCourse.id, 'trainees:', loadedCourse.trainees?.length || 0);
                            setSelectedCourse(loadedCourse);
                        }
                    }
                } else {
                    // FIX: If no saved state at all, try to use the initially loaded course from backend
                    const loadedCourse = courses.find((c) => c.loaded === true);
                    if (loadedCourse) {
                        console.log('No saved state, using loaded course:', loadedCourse.id, 'trainees:', loadedCourse.trainees?.length || 0);
                        setSelectedCourse(loadedCourse);
                    }
                }
            } catch (error) {
                console.error('Error loading mentor storage state:', error);
                localStorage.removeItem(STORAGE_KEY);

                // FIX: Even on error, try to use loaded course
                const loadedCourse = courses.find((c) => c.loaded === true);
                if (loadedCourse) {
                    console.log('Error loading state, using loaded course:', loadedCourse.id);
                    setSelectedCourse(loadedCourse);
                }
            }
            setIsInitialized(true);
        };

        if (courses.length > 0) {
            loadState();
        }
    }, [courses.length]); // Only depend on length to avoid re-running when courses update

    // FIX: Update selectedCourse when courses array changes (e.g., when trainees are loaded)
    useEffect(() => {
        if (!isInitialized || !selectedCourse) return;

        const updatedCourse = courses.find((c) => c.id === selectedCourse.id);
        if (updatedCourse) {
            // Check if the course data has been updated (loaded flag or trainee count changed)
            const hasNewData =
                updatedCourse.loaded !== selectedCourse.loaded || (updatedCourse.trainees?.length || 0) !== (selectedCourse.trainees?.length || 0);

            if (hasNewData) {
                console.log(
                    'Course data updated:',
                    updatedCourse.id,
                    'loaded:',
                    updatedCourse.loaded,
                    'trainees:',
                    updatedCourse.trainees?.length || 0,
                );
                setSelectedCourse(updatedCourse);
            }
        }
    }, [courses, isInitialized]);

    // Save state to localStorage whenever it changes
    useEffect(() => {
        if (!isInitialized) return;

        try {
            const state: MentorStorageState = {
                activeCategory,
                selectedCourseId: selectedCourse?.id || null,
            };
            localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch (error) {
            console.error('Error saving mentor storage state:', error);
        }
    }, [activeCategory, selectedCourse, isInitialized]);

    const updateActiveCategory = (category: string) => {
        setActiveCategory(category);
    };

    const updateSelectedCourse = (course: MentorCourse | null) => {
        setSelectedCourse(course);
    };

    const clearStorage = () => {
        try {
            localStorage.removeItem(STORAGE_KEY);
            setActiveCategory('RTG');
            setSelectedCourse(null);
        } catch (error) {
            console.error('Error clearing mentor storage:', error);
        }
    };

    return {
        activeCategory,
        selectedCourse,
        setActiveCategory: updateActiveCategory,
        setSelectedCourse: updateSelectedCourse,
        clearStorage,
        isInitialized,
    };
}