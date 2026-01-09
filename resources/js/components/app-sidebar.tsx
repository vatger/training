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
import {
    BookOpen,
    BookOpenIcon,
    Calendar1,
    CheckCircle,
    CircleCheck,
    ClipboardList,
    Database,
    Folder,
    GraduationCap,
    LayoutGrid,
    Search,
    Users,
} from 'lucide-react';
import { useState } from 'react';
import AppLogo from './app-logo';

const navSections = [
    {
        label: 'General',
        items: [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
        ] as NavItem[],
    },
];

const trainingSection = (isOBS: unknown) => {
    return {
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
            ...(isOBS === true
                ? [
                      {
                          title: 'S1 Training',
                          href: route('s1.training'),
                          icon: BookOpenIcon,
                      },
                  ]
                : []),
        ] as NavItem[],
    };
};

const mentorSection = (isS1Mentor: unknown) => {
    return {
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
            ...(isS1Mentor
                ? [
                      {
                          title: 'S1 Overview',
                          href: route('s1.mentor.index'),
                          icon: Calendar1,
                      },
                  ]
                : []),
        ] as NavItem[],
    };
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

const lmSection = {
    label: 'Management',
    items: [
        {
            title: 'Edit Courses',
            href: '/admin',
            icon: Database,
            external: true,
        },
    ],
};

const footerNavItems: NavItem[] = [
    {
        title: 'GDPR',
        href: 'https://vatsim-germany.org/policies/gdpr',
        icon: Folder,
    },
    {
        title: 'Imprint',
        href: 'https://vatsim-germany.org/policies/imprint',
        icon: BookOpen,
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
    const isS1Mentor = auth.user?.is_s1_mentor || auth.user?.is_superuser || auth.user?.is_admin;
    const isSuperuser = auth.user?.is_superuser || auth.user?.is_admin;
    const isLeadingMentor = auth.user?.is_leading_mentor;
    const [searchModalOpen, setSearchModalOpen] = useState(false);
    const isOBS = auth.user?.rating === 0 || auth.user?.rating === 1 || auth.user?.is_admin; // No superuser check, only admin users can see

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
                    {navSections
                        .filter((section) => section.label !== 'Training')
                        .map((section) => (
                            <NavSection key={section.label} section={section} />
                        ))}

                    <NavSection section={trainingSection(isOBS)} />

                    {isMentor === true && (
                        <>
                            <NavSection section={mentorSection(isS1Mentor)} />
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

                    {isLeadingMentor === true && (
                        <>
                            <NavSection section={lmSection} />
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
