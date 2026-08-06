
import {CircleCheckIcon, InfoIcon, Loader2Icon, OctagonXIcon, TriangleAlertIcon,} from 'lucide-react';
import {Toaster as Sonner, type ToasterProps} from 'sonner';
import {cn} from '@/lib/utils';

const Toaster = ({ ...props }: ToasterProps) => {
    return (
        <Sonner
            toastOptions={{
                unstyled: false,
                classNames: {
                    info: '!text-primary dark:[&_[data-description]]:!text-secondary-200',
                    closeButton:
                        '!border-secondary-200 dark:!bg-secondary-200 dark:hover:!bg-secondary-50',
                    success: cn(
                        '!bg-success-50 !border-success-400 !text-success-800 [&_[data-description]]:!text-vatger-success',
                        'dark:!bg-success-900 dark:!border-success-800 dark:!text-success-100 dark:[&_[data-description]]:!text-success-100',
                    ),
                    warning: cn(
                        '!bg-warning-50 !border-warning-400 !text-warning-800 [&_[data-description]]:!text-warning-800',
                        'dark:!bg-warning-900 dark:!border-warning-800 dark:!text-warning-200 dark:[&_[data-description]]:!text-warning-300',
                    ),
                    error: cn(
                        '!bg-danger-50 !border-danger-400 !text-vatger-danger [&_[data-description]]:!text-danger-600',
                        'dark:!bg-danger-900 dark:!border-danger-800 dark:!text-danger-200 dark:[&_[data-description]]:!text-danger-300',
                    ),
                    icon: '',
                },
            }}
            className='toaster group'
            icons={{
                success: <CircleCheckIcon className='size-4' />,
                info: <InfoIcon className='size-4' />,
                warning: <TriangleAlertIcon className='size-4' />,
                error: <OctagonXIcon className='size-4' />,
                loading: <Loader2Icon className='size-4 animate-spin' />,
            }}
            style={
                {
                    '--normal-bg': 'var(--popover)',
                    '--normal-text': 'var(--popover-foreground)',
                    '--normal-border': 'var(--border)',
                    '--border-radius': 'var(--radius)',
                } as React.CSSProperties
            }
            {...props}
        />
    );
};

export { Toaster };

