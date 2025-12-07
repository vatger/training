import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Loader2, Plus, AlertCircle, CheckCircle } from 'lucide-react';
import axios from 'axios';

interface User {
    id: number;
    vatsim_id: number;
    name: string;
    email?: string;
    rating?: number;
    subdivision?: string;
}

interface AddTraineeProps {
    courseId: number;
    variant?: 'inline' | 'modal';
    onSuccess?: () => void;
}

export function AddTrainee({ courseId, variant = 'inline', onSuccess }: AddTraineeProps) {
    const [vatsimId, setVatsimId] = useState('');
    const [isSearching, setIsSearching] = useState(false);
    const [isAdding, setIsAdding] = useState(false);
    const [foundUser, setFoundUser] = useState<User | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [showModal, setShowModal] = useState(false);

    const handleSearch = async () => {
        if (!vatsimId || vatsimId.length < 6) {
            setError('Please enter a valid VATSIM ID (at least 6 digits)');
            return;
        }

        setIsSearching(true);
        setError(null);
        setFoundUser(null);

        try {
            const response = await axios.post(route('users.search'), {
                query: vatsimId,
            });

            if (response.data.success && response.data.users.length > 0) {
                // Find exact VATSIM ID match
                const user = response.data.users.find(
                    (u: User) => u.vatsim_id.toString() === vatsimId
                );

                if (user) {
                    setFoundUser(user);
                    setError(null);
                } else {
                    setError(`No user found with VATSIM ID: ${vatsimId}`);
                }
            } else {
                setError(`No user found with VATSIM ID: ${vatsimId}`);
            }
        } catch (err) {
            console.error('Search error:', err);
            setError('Failed to search for user. Please try again.');
        } finally {
            setIsSearching(false);
        }
    };

    const handleAdd = () => {
        if (!foundUser) return;

        setIsAdding(true);
        router.post(
            route('overview.add-trainee-to-course'),
            {
                course_id: courseId,
                user_id: foundUser.id,
            },
            {
                onSuccess: () => {
                    setVatsimId('');
                    setFoundUser(null);
                    setError(null);
                    setShowModal(false);
                    if (onSuccess) onSuccess();
                },
                onError: (errors) => {
                    const errorMessage = Object.values(errors).flat()[0];
                    setError(typeof errorMessage === 'string' ? errorMessage : 'Failed to add trainee');
                },
                onFinish: () => {
                    setIsAdding(false);
                },
            }
        );
    };

    const handleKeyPress = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter' && !foundUser) {
            handleSearch();
        }
    };

    const handleReset = () => {
        setVatsimId('');
        setFoundUser(null);
        setError(null);
    };

    if (variant === 'modal') {
        return (
            <>
                <Button size="sm" onClick={() => setShowModal(true)}>
                    <Plus className="mr-2 h-4 w-4" />
                    Add Trainee
                </Button>

                <Dialog open={showModal} onOpenChange={setShowModal}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Add Trainee to Course</DialogTitle>
                            <DialogDescription>
                                Search for a user by VATSIM ID and add them to this course
                            </DialogDescription>
                        </DialogHeader>

                        <div className="space-y-4 py-4">
                            <div className="space-y-2">
                                <Label htmlFor="vatsim-id-modal">VATSIM ID</Label>
                                <div className="flex gap-2">
                                    <Input
                                        id="vatsim-id-modal"
                                        type="number"
                                        placeholder="1234567"
                                        value={vatsimId}
                                        onChange={(e) => setVatsimId(e.target.value)}
                                        onKeyPress={handleKeyPress}
                                        disabled={isSearching || !!foundUser}
                                    />
                                    {!foundUser ? (
                                        <Button
                                            onClick={handleSearch}
                                            disabled={isSearching || !vatsimId}
                                        >
                                            {isSearching ? (
                                                <Loader2 className="h-4 w-4 animate-spin" />
                                            ) : (
                                                'Search'
                                            )}
                                        </Button>
                                    ) : (
                                        <Button variant="outline" onClick={handleReset}>
                                            Reset
                                        </Button>
                                    )}
                                </div>
                            </div>

                            {error && (
                                <Alert variant="destructive">
                                    <AlertCircle className="h-4 w-4" />
                                    <AlertDescription>{error}</AlertDescription>
                                </Alert>
                            )}

                            {foundUser && (
                                <Alert>
                                    <CheckCircle className="h-4 w-4" />
                                    <AlertDescription>
                                        <div className="space-y-1">
                                            <div className="font-medium">{foundUser.name}</div>
                                            <div className="text-sm">
                                                VATSIM ID: {foundUser.vatsim_id}
                                            </div>
                                            {foundUser.rating && (
                                                <div className="text-sm">Rating: {foundUser.rating}</div>
                                            )}
                                            {foundUser.subdivision && (
                                                <div className="text-sm">
                                                    Subdivision: {foundUser.subdivision}
                                                </div>
                                            )}
                                        </div>
                                    </AlertDescription>
                                </Alert>
                            )}
                        </div>

                        <DialogFooter>
                            <Button variant="outline" onClick={() => setShowModal(false)}>
                                Cancel
                            </Button>
                            <Button
                                onClick={handleAdd}
                                disabled={!foundUser || isAdding}
                            >
                                {isAdding ? (
                                    <>
                                        <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                        Adding...
                                    </>
                                ) : (
                                    <>
                                        <Plus className="mr-2 h-4 w-4" />
                                        Add Trainee
                                    </>
                                )}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </>
        );
    }

    // Inline variant
    return (
        <div className="space-y-3">
            <div className="flex items-center gap-2">
                <div className="flex-1">
                    <Input
                        type="number"
                        placeholder="VATSIM ID"
                        value={vatsimId}
                        onChange={(e) => setVatsimId(e.target.value)}
                        onKeyPress={handleKeyPress}
                        disabled={isSearching || !!foundUser}
                        className="w-full"
                    />
                </div>
                {!foundUser ? (
                    <Button
                        size="sm"
                        onClick={handleSearch}
                        disabled={isSearching || !vatsimId}
                    >
                        {isSearching ? (
                            <Loader2 className="h-4 w-4 animate-spin" />
                        ) : (
                            'Search'
                        )}
                    </Button>
                ) : (
                    <Button size="sm" variant="outline" onClick={handleReset}>
                        Reset
                    </Button>
                )}
            </div>

            {error && (
                <Alert variant="destructive" className="py-2">
                    <AlertCircle className="h-4 w-4" />
                    <AlertDescription className="text-sm">{error}</AlertDescription>
                </Alert>
            )}

            {foundUser && (
                <div className="space-y-2">
                    <Alert className="py-3">
                        <CheckCircle className="h-4 w-4" />
                        <AlertDescription>
                            <div className="space-y-1">
                                <div className="font-medium">{foundUser.name}</div>
                                <div className="text-sm text-muted-foreground">
                                    VATSIM ID: {foundUser.vatsim_id}
                                </div>
                            </div>
                        </AlertDescription>
                    </Alert>
                    <Button
                        size="sm"
                        onClick={handleAdd}
                        disabled={isAdding}
                        className="w-full"
                    >
                        {isAdding ? (
                            <>
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                Adding...
                            </>
                        ) : (
                            <>
                                <Plus className="mr-2 h-4 w-4" />
                                Add {foundUser.name} to Course
                            </>
                        )}
                    </Button>
                </div>
            )}
        </div>
    );
}