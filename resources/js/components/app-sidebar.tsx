import { Link } from '@inertiajs/react';
import {
    BookOpen,
    Bot,
    FolderGit2,
    LayoutGrid,
    Link2,
    ShieldAlert,
    ShieldCheck,
    TriangleAlert,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as adverseEventsIndex } from '@/routes/adverse-events';
import { index as aiSystemsIndex } from '@/routes/ai-systems';
import { index as linksIndex } from '@/routes/links';
import { index as mitigationsIndex } from '@/routes/mitigations';
import { index as ownersIndex } from '@/routes/owners';
import { index as risksIndex } from '@/routes/risks';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'AI systems',
        href: aiSystemsIndex(),
        icon: Bot,
    },
    {
        title: 'Risks',
        href: risksIndex(),
        icon: ShieldAlert,
    },
    {
        title: 'Adverse events',
        href: adverseEventsIndex(),
        icon: TriangleAlert,
    },
    {
        title: 'Mitigations',
        href: mitigationsIndex(),
        icon: ShieldCheck,
    },
    {
        title: 'Links',
        href: linksIndex(),
        icon: Link2,
    },
    {
        title: 'Owners',
        href: ownersIndex(),
        icon: Users,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    return (
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
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
