import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Trash2, Shield, User } from 'lucide-react';

interface User {
    id: number;
    vatsim_id?: number;
    name: string;
    first_name?: string;
    last_name?: string;
    email: string;
    rating?: number;
    subdivision?: string;
    is_staff: boolean;
    is_superuser: boolean;
    is_admin?: boolean;
    is_vatsim_user: boolean;
    roles?: string[];
}

interface ProfileProps {
    status?: string;
}

export default function Profile({ status }: ProfileProps) {
    const { auth } = usePage().props as any;
    const user: User = auth.user;

    const { data, setData, patch, errors, processing, recentlySuccessful } = useForm({
        name: user.name || '',
        email: user.email || '',
    });

    const { delete: destroy, processing: processingDelete } = useForm();

    const submit = (e: FormEvent) => {
        e.preventDefault();
        patch('/settings/profile');
    };

    const deleteAccount = () => {
        if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
            destroy('/settings/profile', {
                preserveScroll: true,
            });
        }
    };

    return (
        <>
            <Head title="Profile Settings" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* User Type Indicator */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                {user.is_admin ? (
                                    <>
                                        <Shield className="w-5 h-5 text-red-600" />
                                        Administrator Account
                                    </>
                                ) : (
                                    <>
                                        <User className="w-5 h-5 text-blue-600" />
                                        VATSIM Account
                                    </>
                                )}
                            </CardTitle>
                            <CardDescription>
                                {user.is_admin 
                                    ? 'This is an administrator account with full system access.'
                                    : `Connected to VATSIM ID: ${user.vatsim_id || 'Unknown'}`
                                }
                            </CardDescription>
                        </CardHeader>
                        {user.is_vatsim_user && (
                            <CardContent>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <Label className="font-medium">Rating</Label>
                                        <p className="text-gray-600">{user.rating || 'Unknown'}</p>
                                    </div>
                                    <div>
                                        <Label className="font-medium">Subdivision</Label>
                                        <p className="text-gray-600">{user.subdivision || 'None'}</p>
                                    </div>
                                    {user.roles && user.roles.length > 0 && (
                                        <div className="md:col-span-2">
                                            <Label className="font-medium">Roles</Label>
                                            <div className="flex flex-wrap gap-2 mt-1">
                                                {user.roles.map((role) => (
                                                    <span
                                                        key={role}
                                                        className="px-2 py-1 bg-blue-100 text-blue-800 rounded-md text-xs"
                                                    >
                                                        {role}
                                                    </span>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        )}
                    </Card>

                    {/* Profile Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Profile Information</CardTitle>
                            <CardDescription>
                                Update your account's profile information and email address.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {status && (
                                <Alert className="mb-4">
                                    <AlertDescription>{status}</AlertDescription>
                                </Alert>
                            )}

                            <form onSubmit={submit} className="space-y-6">
                                <div className="space-y-2">
                                    <Label htmlFor="name">Name</Label>
                                    <Input
                                        id="name"
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        required
                                        autoComplete="name"
                                    />
                                    {errors.name && (
                                        <p className="text-sm text-red-600">{errors.name}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        required
                                        autoComplete="email"
                                    />
                                    {errors.email && (
                                        <p className="text-sm text-red-600">{errors.email}</p>
                                    )}
                                </div>

                                <div className="flex items-center gap-4">
                                    <Button type="submit" disabled={processing}>
                                        Save Changes
                                    </Button>

                                    {recentlySuccessful && (
                                        <p className="text-sm text-green-600">Saved successfully!</p>
                                    )}
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    {/* Delete Account Section - Only for VATSIM users */}
                    {!user.is_admin && (
                        <Card className="border-red-200">
                            <CardHeader>
                                <CardTitle className="text-red-600 flex items-center gap-2">
                                    <Trash2 className="w-5 h-5" />
                                    Delete Account
                                </CardTitle>
                                <CardDescription>
                                    Permanently delete your account and all of its data.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Alert variant="destructive">
                                    <AlertDescription>
                                        <strong>Warning:</strong> Once your account is deleted, all of its resources and data will be permanently deleted. 
                                        Before deleting your account, please download any data or information that you wish to retain.
                                    </AlertDescription>
                                </Alert>

                                <div className="mt-4">
                                    <Button
                                        variant="destructive"
                                        onClick={deleteAccount}
                                        disabled={processingDelete}
                                    >
                                        {processingDelete ? 'Deleting Account...' : 'Delete Account'}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Admin Account Notice */}
                    {user.is_admin && (
                        <Card className="border-yellow-200 bg-yellow-50">
                            <CardContent className="pt-6">
                                <div className="flex items-center gap-2 text-yellow-800">
                                    <Shield className="w-5 h-5" />
                                    <p className="text-sm">
                                        <strong>Administrator accounts cannot be deleted through the interface.</strong> 
                                        Contact a system administrator if you need to remove this account.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </>
    );
}