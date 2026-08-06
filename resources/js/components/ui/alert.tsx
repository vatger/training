import {cva, type VariantProps} from 'class-variance-authority';
import * as React from 'react';

import {cn} from '@/lib/utils';

const alertVariants = cva(
    'relative grid w-full grid-cols-[0_1fr] items-start gap-y-0.5 rounded-lg border px-4 py-3 text-sm has-[>svg]:grid-cols-[calc(var(--spacing)*4)_1fr] has-[>svg]:gap-x-3 [&>svg]:row-span-2 [&>svg]:my-auto [&>svg]:size-4 [&>svg]:translate-y-0.5 [&>svg]:text-current',
    {
        variants: {
            variant: {
                default:
                    'border text-primary *:data-[slot=alert-description]:text-primary-800 dark:border-secondary-700 dark:*:data-[slot=alert-description]:text-secondary-200 [&>svg]:text-primary',
                success:
                    'border border-success-400 bg-success-50 text-success-800 *:data-[slot=alert-description]:text-vatger-success dark:border-success-800 dark:bg-success-900 dark:text-success-100 dark:*:data-[slot=alert-description]:text-success-200 [&>svg]:text-success-800 dark:[&>svg]:text-success-100',
                warning:
                    'border border-warning-400 bg-warning-50 text-warning-800 *:data-[slot=alert-description]:text-warning-800 dark:border-warning-800 dark:bg-warning-900 dark:text-warning-200 dark:*:data-[slot=alert-description]:text-warning-300 [&>svg]:text-warning-800 dark:[&>svg]:text-warning-200',
                destructive:
                    'border border-danger-400 bg-danger-50 text-vatger-danger *:data-[slot=alert-description]:text-danger-600 dark:border-danger-800 dark:bg-danger-900 dark:text-danger-200 dark:*:data-[slot=alert-description]:text-danger-300 [&>svg]:text-vatger-danger dark:[&>svg]:text-danger-200',
            },
        },
        defaultVariants: {
            variant: 'default',
        },
    },
);

function Alert({
    className,
    variant,
    ...props
}: React.ComponentProps<'div'> & VariantProps<typeof alertVariants>) {
    return (
        <div
            data-slot='alert'
            role='alert'
            className={cn(alertVariants({ variant }), className)}
            {...props}
        />
    );
}

function AlertTitle({ className, ...props }: React.ComponentProps<'div'>) {
    return (
        <div
            data-slot='alert-title'
            className={cn('col-start-2 line-clamp-1 min-h-4 font-medium tracking-tight', className)}
            {...props}
        />
    );
}

function AlertDescription({ className, ...props }: React.ComponentProps<'div'>) {
    return (
        <div
            data-slot='alert-description'
            className={cn(
                'col-start-2 grid justify-items-start gap-1 text-muted-foreground text-sm [&_p]:leading-relaxed',
                className,
            )}
            {...props}
        />
    );
}

export { Alert, AlertTitle, AlertDescription };

