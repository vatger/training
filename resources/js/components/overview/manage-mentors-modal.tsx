import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Mentor, MentorCourse } from '@/types/mentor';
import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Loader2, UserMinus, UserPlus, X } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import axios from 'axios';

interface User {
    id: number;
    vatsim_id: number;
    name: string;
    email?: string;
}

interface ManageMentorsModalProps {
    course: MentorCourse | null;
    isOpen: boolean;
    onClose: () => void;
}

export function ManageMentorsModal({ course, isOpen, onClose }: ManageMentorsModalProps) {
    const [mentors, setMentors] = useState<Mentor[]>([]);
    const [isLoading, setIsLoading] = useState(false);
    const [isRemoving, setIsRemoving] = useState<number | null>(null);
    const [isAdding, setIsAdding] = useState(false);

    // Add mentor state
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState<User[]>([]);
    const [isSearching, setIsSearching] = useState(false);
    const [showAddSection, setShowAddSection] = useState(false);

    useEffect(() => {
        if (isOpen && course) {
            fetchMentors();
        }
    }, [isOpen, course]);

    const fetchMentors = async () => {
        if (!course) return;

        setIsLoading(true);
        try {
            const response = await axios.get(route('overview.get-course-mentors', course.id));
            setMentors(response.data);
        } catch (error) {
            console.error('Error fetching mentors:', error);
        } finally {
            setIsLoading(false);
        }
    };

    const handleSearch = async () => {
        if (searchQuery.length < 2) return;

        setIsSearching(true);
        try {
            const response = await axios.post(route('users.search'), {
                query: searchQuery,
            });

            if (response.data.success) {
                // Filter out users who are already mentors
                const filtered = response.data.users.filter((user: User) => !mentors.some((m) => m.id === user.id));
                setSearchResults(filtered);
            }
        } catch (error) {
            console.error('Search error:', error);
        } finally {
            setIsSearching(false);
        }
    };

    const handleAddMentor = (user: User) => {
        if (!course) return;

        setIsAdding(true);
        router.post(
            route('overview.add-mentor'),
            {
                course_id: course.id,
                user_id: user.id,
            },
            {
                onSuccess: () => {
                    fetchMentors();
                    setSearchQuery('');
                    setSearchResults([]);
                    setShowAddSection(false);
                },
                onFinish: () => {
                    setIsAdding(false);
                },
            },
        );
    };

    const handleRemoveMentor = (mentorId: number) => {
        if (!course) return;

        setIsRemoving(mentorId);
        router.post(
            route('overview.remove-mentor'),
            {
                course_id: course.id,
                mentor_id: mentorId,
            },
            {
                onSuccess: () => {
                    fetchMentors();
                },
                onFinish: () => {
                    setIsRemoving(null);
                },
            },
        );
    };

    const handleClose = () => {
        setShowAddSection(false);
        setSearchQuery('');
        setSearchResults([]);
        onClose();
    };

    return (
        <Dialog open={isOpen} onOpenChange={handleClose}>
            <DialogContent className="max-h-[85vh] max-w-4xl overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Manage Mentors - {course?.name}</DialogTitle>
                    <DialogDescription>Add or remove mentors who can access this course's trainees</DialogDescription>
                </DialogHeader>

                <div className="space-y-6 py-4">
                    {/* Current Mentors */}
                    <div>
                        <div className="mb-4 flex items-center justify-between">
                            <h3 className="text-sm font-medium">Current Mentors ({mentors.length})</h3>
                            {!showAddSection && (
                                <Button size="sm" onClick={() => setShowAddSection(true)}>
                                    <UserPlus className="mr-2 h-4 w-4" />
                                    Add Mentor
                                </Button>
                            )}
                        </div>

                        {isLoading ? (
                            <div className="flex items-center justify-center py-8">
                                <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                            </div>
                        ) : mentors.length > 0 ? (
                            <div className="rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Name</TableHead>
                                            <TableHead>VATSIM ID</TableHead>
                                            <TableHead className="text-right">Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {mentors.map((mentor) => (
                                            <TableRow key={mentor.id}>
                                                <TableCell className="font-medium">{mentor.name}</TableCell>
                                                <TableCell>
                                                    <Badge variant="outline">{mentor.vatsim_id}</Badge>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button
                                                        size="sm"
                                                        variant="destructive"
                                                        onClick={() => handleRemoveMentor(mentor.id)}
                                                        disabled={isRemoving === mentor.id || mentors.length === 1}
                                                    >
                                                        {isRemoving === mentor.id ? (
                                                            <>
                                                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                                                Removing...
                                                            </>
                                                        ) : (
                                                            <>
                                                                <UserMinus className="mr-2 h-4 w-4" />
                                                                Remove
                                                            </>
                                                        )}
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        ) : (
                            <div className="rounded-lg border border-dashed py-8 text-center text-sm text-muted-foreground">
                                No mentors assigned to this course
                            </div>
                        )}
                    </div>

                    {/* Add Mentor Section */}
                    {showAddSection && (
                        <div className="space-y-4 rounded-lg border p-4">
                            <div className="flex items-center justify-between">
                                <h3 className="text-sm font-medium">Add New Mentor</h3>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    onClick={() => {
                                        setShowAddSection(false);
                                        setSearchQuery('');
                                        setSearchResults([]);
                                    }}
                                >
                                    <X className="h-4 w-4" />
                                </Button>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="mentor-search">Search for user</Label>
                                <div className="flex gap-2">
                                    <Input
                                        id="mentor-search"
                                        placeholder="Name or VATSIM ID..."
                                        value={searchQuery}
                                        onChange={(e) => setSearchQuery(e.target.value)}
                                        onKeyPress={(e) => {
                                            if (e.key === 'Enter') {
                                                handleSearch();
                                            }
                                        }}
                                    />
                                    <Button onClick={handleSearch} disabled={isSearching || searchQuery.length < 2}>
                                        {isSearching ? <Loader2 className="h-4 w-4 animate-spin" /> : 'Search'}
                                    </Button>
                                </div>
                            </div>

                            {searchResults.length > 0 && (
                                <div className="space-y-2">
                                    <Label>Search Results</Label>
                                    <div className="space-y-2">
                                        {searchResults.map((user) => (
                                            <div key={user.id} className="flex items-center justify-between rounded-lg border p-3">
                                                <div>
                                                    <div className="font-medium">{user.name}</div>
                                                    <div className="text-sm text-muted-foreground">VATSIM ID: {user.vatsim_id}</div>
                                                </div>
                                                <Button size="sm" onClick={() => handleAddMentor(user)} disabled={isAdding}>
                                                    {isAdding ? (
                                                        <Loader2 className="h-4 w-4 animate-spin" />
                                                    ) : (
                                                        <>
                                                            <UserPlus className="mr-2 h-4 w-4" />
                                                            Add
                                                        </>
                                                    )}
                                                </Button>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {searchResults.length === 0 && !isSearching && (
                                <div className="rounded-lg border border-dashed py-4 text-center text-sm text-muted-foreground">
                                    No users found matching "{searchQuery}"
                                </div>
                            )}
                        </div>
                    )}
                </div>

                <DialogFooter>
                    <Button variant="outline" onClick={handleClose}>
                        Close
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}