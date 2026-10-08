import { Link } from '@inertiajs/react';
import {
    Bot,
    LayoutGrid,
    Link2,
    ShieldAlert,
    ShieldCheck,
    TriangleAlert,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
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
import { useTranslations } from '@/hooks/use-translations';
import { dashboard } from '@/routes';
import { index as adverseEventsIndex } from '@/routes/adverse-events';
import { index as aiSystemsIndex } from '@/routes/ai-systems';
import { index as linksIndex } from '@/routes/links';
import { index as mitigationsIndex } from '@/routes/mitigations';
import { index as ownersIndex } from '@/routes/owners';
import { index as risksIndex } from '@/routes/risks';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { t } = useTranslations();

    const mainNavItems: NavItem[] = [
        {
            title: t('Dashboard'),
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: t('AI systems'),
            href: aiSystemsIndex(),
            icon: Bot,
        },
        {
            title: t('Risks'),
            href: risksIndex(),
            icon: ShieldAlert,
        },
        {
            title: t('Adverse events'),
            href: adverseEventsIndex(),
            icon: TriangleAlert,
        },
        {
            title: t('Mitigations'),
            href: mitigationsIndex(),
            icon: ShieldCheck,
        },
        {
            title: t('Links'),
            href: linksIndex(),
            icon: Link2,
        },
        {
            title: t('Owners'),
            href: ownersIndex(),
            icon: Users,
        },
    ];

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
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
