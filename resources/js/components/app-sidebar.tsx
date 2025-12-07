import { NavFooter } from '@/components/layout/nav-footer';
import { NavUser } from '@/components/layout/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { UserSearchModal } from '@/components/user-search-modal';
import { dashboard } from '@/routes';
import { SharedData, type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BookOpenIcon, CheckCircle, CircleCheck, ClipboardList, Database, Globe, GraduationCap, LayoutGrid, Search, Send, Users } from 'lucide-react';
import { useState } from 'react';
import AppLogo from './app-logo';

const navSections = [
    {
        label: 'Platform',
        items: [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
        ] as NavItem[],
    },
    {
        label: 'Training',
        items: [
            {
                title: 'Courses',
                href: route('courses.index'),
                icon: BookOpenIcon,
            },
            {
                title: 'Endorsements',
                href: route('endorsements'),
                icon: CircleCheck,
            },
        ] as NavItem[],
    },
];

const mentorSection = {
    label: 'Mentoring',
    items: [
        {
            title: 'Overview',
            href: route('overview.index'),
            icon: Users,
        },
        {
            title: 'Waiting Lists',
            href: route('waiting-lists.manage'),
            icon: ClipboardList,
        },
        {
            title: 'Endorsement Management',
            href: route('endorsements.manage'),
            icon: CheckCircle,
        },
    ] as NavItem[],
};

const atdSection = {
    label: 'Examination',
    items: [
        {
            title: 'CPT Planning',
            href: route('cpt.index'),
            icon: GraduationCap,
        },
    ],
};

const adminSection = {
    label: 'Admin',
    items: [
        /* {
            title: 'Announcements',
            href: '#',
            icon: Megaphone,
        }, */
        {
            title: 'Database',
            href: '/admin',
            icon: Database,
            external: true,
        },
    ] as NavItem[],
};

const footerNavItems: NavItem[] = [
    {
        title: 'Homepage',
        href: 'https://vatsim-germany.org',
        icon: Globe,
    },
    {
        title: 'Forum',
        href: 'https://board.vatsim-germany.org',
        icon: Send,
    },
];

function NavSection({ section }: { section: (typeof navSections)[0] }) {
    const page = usePage();

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>{section.label}</SidebarGroupLabel>
            <SidebarMenu>
                {section.items.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton
                            asChild
                            isActive={page.url.startsWith(typeof item.href === 'string' ? item.href : item.href.url)}
                            tooltip={{ children: item.title }}
                        >
                            {item.external ? (
                                <a href={item.href as string}>
                                    {item.icon && <item.icon />}
                                    <span>{item.title}</span>
                                </a>
                            ) : (
                                <Link href={item.href} prefetch>
                                    {item.icon && <item.icon />}
                                    <span>{item.title}</span>
                                </Link>
                            )}
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;
    const isMentor = auth.user?.is_mentor || auth.user?.is_superuser || auth.user?.is_admin;
    const isSuperuser = auth.user?.is_superuser || auth.user?.is_admin;
    const [searchModalOpen, setSearchModalOpen] = useState(false);

    return (
        <>
            <Sidebar collapsible="icon" variant="inset">
                <SidebarHeader>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton size="lg" asChild>
                                <Link href={dashboard()} prefetch>
                                    <AppLogo />
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarHeader>

                <SidebarContent>
                    {navSections.map((section) => (
                        <NavSection key={section.label} section={section} />
                    ))}

                    {isMentor === true && (
                        <>
                            <NavSection section={mentorSection} />
                            <SidebarGroup className="px-2 py-0">
                                <SidebarMenu>
                                    <SidebarMenuItem>
                                        <SidebarMenuButton
                                            className="cursor-pointer"
                                            onClick={() => setSearchModalOpen(true)}
                                            tooltip={{ children: 'Find User' }}
                                        >
                                            <Search />
                                            <span>Find User</span>
                                        </SidebarMenuButton>
                                    </SidebarMenuItem>
                                </SidebarMenu>
                            </SidebarGroup>
                        </>
                    )}

                    {isMentor === true && (
                        <>
                            <NavSection section={atdSection} />
                        </>
                    )}

                    {isSuperuser === true && (
                        <>
                            <NavSection section={adminSection} />
                        </>
                    )}
                </SidebarContent>

                <SidebarFooter>
                    <NavFooter items={footerNavItems} className="mt-auto" />
                    <NavUser />
                </SidebarFooter>
            </Sidebar>

            <UserSearchModal open={searchModalOpen} onOpenChange={setSearchModalOpen} />
        </>
    );
}