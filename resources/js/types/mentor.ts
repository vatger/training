export interface SoloStatus {
    remaining: number; // Days until expiry (calculated from expiry - today)
    used: number; // Days since solo was created (position_days from API)
    extensionDaysLeft: number; // Always 31 (maximum extension allowed at one time)
    expiry: string; // Expiry date (YYYY-MM-DD format)
}

export interface Trainee {
    id: number;
    name: string;
    vatsimId: number;
    initials: string;
    progress: boolean[];
    lastSession: string | null;
    nextStep: string;
    claimedBy: string | null;
    claimedByMentorId: number | null;
    soloStatus: SoloStatus | null;
    endorsementStatus: string | null;
    moodleStatus: string | null;
    remark: {
        text: string;
        updated_at: string | null;
        author_initials: string | null;
        author_name: string | null;
    } | null;
}

export interface MentorCourse {
    id: number;
    name: string;
    position: string; // e.g., "TWR", "APP", "CTR", "GND"
    type: 'EDMT' | 'RTG' | 'GST' | 'FAM' | 'RST';
    soloStation: string | null;
    activeTrainees: number;
    trainees: Trainee[];
    loaded?: boolean;
}

export interface MentorStatistics {
    activeTrainees: number;
    claimedTrainees: number;
    trainingSessions: number;
    waitingList: number;
}

export interface MentorOverviewProps {
    courses: MentorCourse[];
    statistics: MentorStatistics;
}

export interface Mentor {
    id: number;
    name: string;
    vatsim_id: string;
    email: string;
}

export interface AssignTraineeData {
    trainee_id: number;
    course_id: number;
    mentor_id: number;
}

export interface ClaimTraineeData {
    trainee_id: number;
    course_id: number;
}

export interface UnclaimTraineeData {
    trainee_id: number;
    course_id: number;
}

export interface RemoveTraineeData {
    trainee_id: number;
    course_id: number;
}

export interface UpdateRemarkData {
    trainee_id: number;
    course_id: number;
    remark: string;
}

export function getCourseTypeDisplay(type: MentorCourse['type']): string {
    const typeMap: Record<MentorCourse['type'], string> = {
        EDMT: 'Endorsement',
        RTG: 'Rating',
        GST: 'Visitor',
        FAM: 'Familiarisation',
        RST: 'Roster Reentry',
    };
    return typeMap[type] || type;
}

export function getVisibleColumnsForCourseType(type: MentorCourse['type']) {
    return {
        solo: type === 'RTG', // Rating courses show solo
        endorsement: type === 'GST', // Ground/Visitor courses show endorsement
        moodleStatus: type === 'EDMT', // Endorsement courses show moodle
    };
}
