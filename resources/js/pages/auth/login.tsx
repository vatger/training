import { Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { LogIn } from 'lucide-react';

interface LoginProps {
    status?: string;
}

export default function Login({ status }: LoginProps) {
    const handleVatsimLogin = () => {
        window.location.href = '/auth/vatsim';
    };

    return (
        <>
            <Head title="Log in" />

            <div className="bg-body flex min-h-screen items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
                <Card className="w-full max-w-md">
                    <CardHeader className="space-y-1">
                        <CardTitle className="text-center text-2xl font-bold">Sign in to your account</CardTitle>
                        <CardDescription className="text-center">Access the VATGER Training System with your VATSIM account</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {status && (
                            <Alert>
                                <AlertDescription>{status}</AlertDescription>
                            </Alert>
                        )}

                        {/* VATSIM OAuth Button */}
                        <Button type="button" onClick={handleVatsimLogin} className="w-full bg-blue-600 text-white hover:bg-blue-700" size="lg">
                            <LogIn />
                            Login with VATGER Connect
                        </Button>

                        <div className="mt-0 text-center text-sm text-gray-600">
                            <p>Only VATSIM Germany members can access this system.</p>
                        </div>

                        {/* Debug/Admin access hint for development */}
                        {process.env.NODE_ENV === 'development' && (
                            <div className="mt-6 rounded-md border border-yellow-200 bg-yellow-50 p-3">
                                <p className="text-xs text-yellow-800">
                                    <strong>Development Mode:</strong> Admin access available at{' '}
                                    <a href="/admin/login" className="underline">
                                        /admin/login
                                    </a>
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}