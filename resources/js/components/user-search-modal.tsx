import { useState } from 'react';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Search, Loader2, User } from 'lucide-react';
import { router } from '@inertiajs/react';
import axios from 'axios';

interface User {
    id: number;
    vatsim_id: number;
    name: string;
    email?: string;
}

interface UserSearchModalProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export function UserSearchModal({ open, onOpenChange }: UserSearchModalProps) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<User[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleSearch = async () => {
        if (query.length < 2) {
            setError('Please enter at least 2 characters');
            return;
        }

        setLoading(true);
        setError(null);
        setResults([]);

        try {
            const response = await axios.post(route('users.search'), {
                query: query
            });

            if (response.data.success) {
                setResults(response.data.users);
                if (response.data.users.length === 0) {
                    setError('No users found');
                }
            } else {
                setError('Search failed');
            }
        } catch (err) {
            console.error('Search error:', err);
            setError('Failed to search users');
        } finally {
            setLoading(false);
        }
    };

    const handleKeyPress = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            handleSearch();
        }
    };

    const handleSelectUser = (user: User) => {
        router.visit(route('users.profile', user.vatsim_id));
        onOpenChange(false);
        // Reset state
        setQuery('');
        setResults([]);
        setError(null);
    };

    const handleClose = (open: boolean) => {
        if (!open) {
            // Reset state when closing
            setQuery('');
            setResults([]);
            setError(null);
        }
        onOpenChange(open);
    };

    return (
        <Dialog open={open} onOpenChange={handleClose}>
            <DialogContent className="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>Find User</DialogTitle>
                    <DialogDescription>Search for a user by name or VATSIM ID</DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    <div className="flex gap-2">
                        <Input
                            placeholder="Enter name or VATSIM ID..."
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            onKeyPress={handleKeyPress}
                            disabled={loading}
                        />
                        <Button onClick={handleSearch} disabled={loading || query.length < 2}>
                            {loading ? <Loader2 className="h-4 w-4 animate-spin" /> : <Search className="h-4 w-4" />}
                        </Button>
                    </div>

                    {error && (
                        <div className="rounded-md bg-destructive/15 p-3 text-sm text-destructive">
                            {error}
                        </div>
                    )}

                    {results.length > 0 && (
                        <ScrollArea className="h-[300px]">
                            <div className="space-y-2">
                                {results.map((user) => (
                                    <button
                                        key={user.id}
                                        onClick={() => handleSelectUser(user)}
                                        className="flex w-full items-center gap-3 rounded-lg border p-3 text-left transition-colors hover:bg-accent"
                                    >
                                        <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                                            <User className="h-5 w-5 text-primary" />
                                        </div>
                                        <div className="flex-1">
                                            <div className="font-medium">{user.name}</div>
                                            <div className="text-sm text-muted-foreground">
                                                VATSIM ID: {user.vatsim_id}
                                            </div>
                                        </div>
                                    </button>
                                ))}
                            </div>
                        </ScrollArea>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}