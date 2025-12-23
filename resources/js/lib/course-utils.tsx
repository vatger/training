import { Radio, TowerControl, Shield, Plane } from 'lucide-react';

export type PositionType = 'GND' | 'TWR' | 'APP' | 'CTR';
export type CourseType = 'RTG' | 'EDMT' | 'FAM' | 'GST' | 'RST';
export type StatusType = 'active' | 'warning' | 'removal' | 'available';

/**
 * Get color classes for position badges
 */
export const getPositionColor = (position: string): string => {
    switch (position) {
        case 'GND':
            return 'bg-gray-100 text-gray-700 dark:bg-gray-900 dark:text-gray-300';
        case 'TWR':
            return 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300';
        case 'APP':
            return 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300';
        case 'CTR':
            return 'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300';
        default:
            return 'bg-gray-100 text-gray-700 dark:bg-gray-900 dark:text-gray-300';
    }
};

/**
 * Get color classes for course type badges
 */
export const getTypeColor = (type: string): string => {
    switch (type) {
        case 'RTG':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 border-blue-200 dark:border-blue-800';
        case 'EDMT':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300 border-purple-200 dark:border-purple-800';
        case 'FAM':
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300 border-yellow-200 dark:border-yellow-800';
        case 'GST':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300 border-green-200 dark:border-green-800';
        case 'RST':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 border-red-200 dark:border-red-800';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300 border-gray-200 dark:border-gray-800';
    }
};

/**
 * Get color classes for status badges
 */
export const getStatusColor = (status: string): string => {
    switch (status) {
        case 'active':
            return 'border-green-200 bg-green-50 text-green-700 dark:border-green-700 dark:bg-green-900 dark:text-green-300';
        case 'warning':
            return 'border-yellow-200 bg-yellow-50 text-yellow-700 dark:border-yellow-700 dark:bg-yellow-900 dark:text-yellow-300';
        case 'removal':
            return 'border-red-200 bg-red-50 text-red-700 dark:border-red-700 dark:bg-red-900 dark:text-red-300';
        case 'available':
            return 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-700 dark:bg-blue-900 dark:text-blue-300';
        case 'completed':
            return 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-700 dark:bg-blue-900 dark:text-blue-300';
        default:
            return 'border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300';
    }
};

/**
 * Get icon component for position type
 */
export const getPositionIcon = (position: string) => {
    switch (position) {
        case 'GND':
        case 'GNDDEL':
            return <Radio className="h-4 w-4" />;
        case 'TWR':
            return <TowerControl className="h-4 w-4" />;
        case 'APP':
            return <Shield className="h-4 w-4" />;
        case 'CTR':
            return <Plane className="h-4 w-4" />;
        default:
            return <Radio className="h-4 w-4" />;
    }
};

/**
 * Get display text for course type
 */
export const getCourseTypeDisplay = (type: string): string => {
    switch (type) {
        case 'RTG':
            return 'Rating';
        case 'EDMT':
            return 'Endorsement';
        case 'FAM':
            return 'Familiarisation';
        case 'GST':
            return 'Visitor';
        case 'RST':
            return 'Roster';
        default:
            return type;
    }
};