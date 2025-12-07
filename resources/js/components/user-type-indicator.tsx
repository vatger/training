import { usePage } from '@inertiajs/react';
import { Shield, User } from 'lucide-react';

export default function UserTypeIndicator() {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    const { auth } = usePage().props as any;
    const user = auth.user;

    if (!user) return null;

    if (user.is_admin) {
        return (
            <div className="flex items-center gap-2 rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-700">
                <Shield className="h-4 w-4" />
                Admin Access
            </div>
        );
    }

    if (user.is_vatsim_user) {
        return (
            <div className="flex items-center gap-2 rounded-full bg-blue-100 px-3 py-1 text-sm font-medium text-blue-700">
                <User className="h-4 w-4" />
                VATSIM: {user.vatsim_id}
            </div>
        );
    }

    return null;
}