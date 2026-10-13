import { usePage } from '@inertiajs/react';
import type { EnumLabels } from '@/lib/enum-labels';

/**
 * Get the labels of the domain enums, as the backend resolves them from the
 * language file, e.g. `enumLabels.CostLevel.high`.
 */
export function useEnumLabels(): EnumLabels {
    return usePage().props.enumLabels;
}
