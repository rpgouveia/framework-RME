import { Link, usePage } from '@inertiajs/react';
import {
    Bot,
    LayoutGrid,
    Link2,
    RotateCcw,
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
import { linksWith } from '@/lib/link-filters';
import { toUrl } from '@/lib/utils';
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
    const { url } = usePage();
    // The queue is the link list filtered, so the path alone cannot tell the
    // two entries apart.
    const onLinks = url.split('?')[0] === toUrl(linksIndex());
    const onQueue =
        onLinks && url.includes('verification=awaiting_reassessment');

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
            isActive: onLinks && !onQueue,
        },
        {
            title: t('Reassessment queue'),
            href: linksWith('awaiting_reassessment'),
            icon: RotateCcw,
            isActive: onQueue,
        },
        {
            title: t('Owners'),
            href: ownersIndex(),
            icon: Users,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="sidebar">
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
