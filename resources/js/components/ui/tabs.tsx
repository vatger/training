
import * as TabsPrimitive from '@radix-ui/react-tabs';
import {cva, type VariantProps} from 'class-variance-authority';
import * as React from 'react';

import {cn} from '@/lib/utils';

function Tabs({ className, ...props }: React.ComponentProps<typeof TabsPrimitive.Root>) {
    return (
        <TabsPrimitive.Root
            data-slot='tabs'
            className={cn('flex flex-col gap-2', className)}
            {...props}
        />
    );
}

const tabsListVariants = cva(
    'inline-flex h-9 w-fit items-center justify-center rounded-lg bg-secondary-100 p-[3px] text-muted-foreground dark:bg-secondary-800',
    {
        variants: {
            variant: {
                onCard: '',
                notOnCard: 'dark:bg-secondary-900',
            },
        },
        defaultVariants: {
            variant: 'onCard',
        },
    },
);

type TabsListProps = React.ComponentProps<typeof TabsPrimitive.List> &
    VariantProps<typeof tabsListVariants>;

function TabsList({ className, variant, ...props }: TabsListProps) {
    return (
        <TabsPrimitive.List
            data-slot='tabs-list'
            className={cn(tabsListVariants({ variant }), className)}
            {...props}
        />
    );
}

const tabsTriggerVariants = cva(
    "inline-flex h-[calc(100%-1px)] flex-1 items-center justify-center gap-1.5 whitespace-nowrap rounded-md border border-transparent px-2 py-1 font-medium text-secondary-700 text-sm transition-[color,box-shadow] hover:not-data-[state=active]:cursor-pointer focus-visible:border-ring focus-visible:outline-1 focus-visible:outline-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:border-secondary-200 data-[state=active]:bg-secondary-50 data-[state=active]:text-primary data-[state=active]:shadow-sm dark:text-secondary-200 dark:data-[state=active]:border-secondary-600 dark:data-[state=active]:bg-secondary-700 dark:data-[state=active]:text-foreground [&_svg:not([class*='size-'])]:size-4 [&_svg]:pointer-events-none [&_svg]:shrink-0",
    {
        variants: {
            variant: {
                onCard: '',
                notOnCard:
                    'dark:data-[state=active]:border-secondary-700 dark:data-[state=active]:bg-secondary-800',
            },
        },
        defaultVariants: {
            variant: 'onCard',
        },
    },
);

type TabsTriggerProps = React.ComponentProps<typeof TabsPrimitive.Trigger> &
    VariantProps<typeof tabsTriggerVariants>;

function TabsTrigger({ className, variant, ...props }: TabsTriggerProps) {
    return (
        <TabsPrimitive.Trigger
            data-slot='tabs-trigger'
            className={cn(tabsTriggerVariants({ variant }), className)}
            {...props}
        />
    );
}

function TabsContent({ className, ...props }: React.ComponentProps<typeof TabsPrimitive.Content>) {
    return (
        <TabsPrimitive.Content
            data-slot='tabs-content'
            className={cn('flex-1 outline-none', className)}
            {...props}
        />
    );
}

export { Tabs, TabsList, TabsTrigger, TabsContent };

