import { useEffect, useState } from 'react';
import axios from 'axios';

interface MoodleStatuses {
    [key: string]: 'completed' | 'in-progress' | 'not-started' | 'unknown';
}

interface FetchProgress {
    loaded: number;
    total: number;
}

async function fetchWithLimit<T>(
    items: T[],
    limit: number,
    fetchFn: (item: T) => Promise<void>
): Promise<void> {
    const queue = [...items];
    const executing: Promise<void>[] = [];

    while (queue.length > 0 || executing.length > 0) {
        while (executing.length < limit && queue.length > 0) {
            const item = queue.shift()!;
            const promise = fetchFn(item).finally(() => {
                executing.splice(executing.indexOf(promise), 1);
            });
            executing.push(promise);
        }

        if (executing.length > 0) {
            await Promise.race(executing);
        }
    }
}

export function useMoodleStatus(trainees: Array<{ id: number; vatsimId: number }>, courseId: number) {
    const [statuses, setStatuses] = useState<MoodleStatuses>({});
    const [progress, setProgress] = useState<FetchProgress>({ loaded: 0, total: 0 });
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (trainees.length === 0) return;

        const fetchStatuses = async () => {
            setLoading(true);
            setProgress({ loaded: 0, total: trainees.length });

            const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            let completed = 0;

            await fetchWithLimit(trainees, 3, async (trainee) => {
                const cacheKey = `${trainee.vatsimId}_${courseId}`;

                try {
                    const response = await axios.post(
                        route('overview.get-moodle-status-trainee'),
                        {
                            trainee_id: trainee.id,
                            course_id: courseId,
                        },
                        {
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                            },
                        }
                    );

                    if (response.data.success && response.data.status) {
                        setStatuses(prev => ({
                            ...prev,
                            [cacheKey]: response.data.status
                        }));
                    }
                } catch (error: any) {
                    console.error(`Failed to fetch Moodle status for trainee ${trainee.id}:`, error);
                    setStatuses(prev => ({
                        ...prev,
                        [cacheKey]: 'unknown'
                    }));
                } finally {
                    completed++;
                    setProgress({ loaded: completed, total: trainees.length });
                }
            });

            setLoading(false);
        };

        fetchStatuses();
    }, [trainees.map(t => t.id).join(','), courseId]);

    return { statuses, loading, progress };
}