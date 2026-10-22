import { Toggletip } from '@/components/help';
import { useTranslations } from '@/hooks/use-translations';

/**
 * The cost rubric (0024, item 5; 0028): cost is the effort of implementing a
 * mitigation, rated on four dimensions, and the highest one wins. The three
 * costs share it, so they stay comparable.
 */
export function CostHelp() {
    const { t } = useTranslations();
    const rows: [string, string, string, string][] = [
        [
            t('Time to deploy'),
            t('Up to about two weeks'),
            t('A few weeks to about three months'),
            t('More than three months'),
        ],
        [
            t('People and skills'),
            t('The system team itself'),
            t('Another area or a specific skill'),
            t('A dedicated team or an outside specialist'),
        ],
        [
            t('Tools and services'),
            t('No new paid tool or service'),
            t('A moderately priced tool or service'),
            t('Dedicated infrastructure or an external audit'),
        ],
        [
            t('Impact on operations'),
            t('No other area changes'),
            t('One existing process changes'),
            t('The architecture or several areas change'),
        ],
    ];

    return (
        <Toggletip label={t('How the cost is rated')}>
            <p>
                {t(
                    'Cost is the effort of implementing, not only money. The highest level among the four dimensions wins.',
                )}
            </p>
            <p className="text-muted-foreground text-xs tracking-[0.32px]">
                {t(
                    'Suggested: the typical effort, from the catalogue. Estimated: the effort planned for this link. Observed: the effort actually spent, reported with the evidence.',
                )}
            </p>
            <table className="w-full text-xs">
                <thead>
                    <tr className="bg-secondary text-left">
                        <th className="p-2 font-semibold">{t('Dimension')}</th>
                        <th className="p-2 font-semibold">
                            {t('Cost Level: Low')}
                        </th>
                        <th className="p-2 font-semibold">
                            {t('Cost Level: Medium')}
                        </th>
                        <th className="p-2 font-semibold">
                            {t('Cost Level: High')}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    {rows.map(([dimension, ...levels]) => (
                        <tr key={dimension} className="border-b align-top">
                            <th className="p-2 text-left font-normal">
                                {dimension}
                            </th>
                            {levels.map((level) => (
                                <td key={level} className="p-2">
                                    {level}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </Toggletip>
    );
}
