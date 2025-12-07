import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Shield, ArrowLeft } from 'lucide-react';

export default function AdminLogin() {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: true,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();

        post('/admin/login', {
            onFinish: () => reset('password'),
        });
    };

    return (
        <>
            <Head title="Admin Login" />

            <div className="bg-body flex min-h-screen items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
                <Card className="w-full max-w-md">
                    <CardHeader className="space-y-1">
                        <div className="mb-4 flex items-center justify-center">
                            <div className="rounded-full bg-red-100 p-3 dark:bg-red-900/20">
                                <Shield className="h-8 w-8 text-red-600 dark:text-red-400" />
                            </div>
                        </div>
                        <CardTitle className="text-center text-2xl font-bold text-red-600 dark:text-red-400">Administrator Access</CardTitle>
                        <CardDescription className="text-center">Development and administrator access only</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <form onSubmit={submit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value={data.email}
                                    className="block w-full"
                                    autoComplete="username"
                                    autoFocus
                                    onChange={(e) => setData('email', e.target.value)}
                                />
                                {errors.email && <p className="text-sm text-red-600">{errors.email}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="password">Password</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    name="password"
                                    value={data.password}
                                    className="block w-full"
                                    autoComplete="current-password"
                                    onChange={(e) => setData('password', e.target.value)}
                                />
                                {errors.password && <p className="text-sm text-red-600">{errors.password}</p>}
                            </div>

                            <Button type="submit" className="w-full bg-red-600 hover:bg-red-700" disabled={processing}>
                                {processing ? 'Signing in...' : 'Sign in as Admin'}
                            </Button>
                        </form>

                        <div className="text-center">
                            <a href="/" className="inline-flex items-center text-sm text-gray-600 hover:text-gray-500">
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Back to VATSIM Login
                            </a>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}