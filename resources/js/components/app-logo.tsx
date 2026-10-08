import { usePage } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    const { name } = usePage().props;

    return (
        <>
            {/*
             * Wrapped: the sidebar menu button clamps its direct `svg`
             * children to `size-4`, and that selector outweighs a utility
             * class on the icon itself.
             */}
            <div className="aspect-square size-10 shrink-0 group-data-[collapsible=icon]:size-8">
                <AppLogoIcon className="size-full" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    {name}
                </span>
            </div>
        </>
    );
}
