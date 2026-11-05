<?php

namespace Database\Seeders\Concerns;

use App\Enums\EvidenceType;
use App\Models\Mitigation;

/**
 * What a seeded piece of evidence says, by type, naming the mitigation it
 * proves, so the evidence of a link reads as proof of that link. Evidence
 * stored after a verification (a renewal) or after a reversal says so at the
 * end, so no two pieces of the same link read the same. The EvidenceFactory
 * keeps its own descriptions, for the tests.
 */
trait DescribesEvidence
{
    /** @var array<string, string> */
    protected const EVIDENCE_DESCRIPTIONS = [
        'document' => 'Procedimento aprovado para “:mitigation”, com o responsável e a periodicidade de execução.',
        'report' => 'Relatório da aplicação de “:mitigation” neste sistema, com os resultados obtidos e as pendências.',
        'audit_log' => 'Registro automático das execuções de “:mitigation” no último ciclo de operação.',
        'test_result' => 'Resultado do teste que confere “:mitigation” na versão em produção do sistema.',
        'certification' => 'Atestado de terceiro independente sobre a aplicação de “:mitigation”.',
        'meeting_minutes' => 'Ata da reunião que aprovou “:mitigation” e definiu o responsável pela execução.',
    ];

    /** The end of a piece of evidence stored after a verification (renewal). */
    protected const AFTER_VERIFICATION = ' Nova comprovação, posterior à última verificação.';

    /** The end of a piece of evidence stored after a reversal. */
    protected const AFTER_REVERSAL = ' Nova comprovação, posterior à reversão.';

    /**
     * The description of a piece of evidence of the mitigation, with the end
     * that says what it came after, if anything.
     */
    protected function describeEvidence(Mitigation $mitigation, EvidenceType $type, string $after = ''): string
    {
        return strtr(self::EVIDENCE_DESCRIPTIONS[$type->value], [':mitigation' => $mitigation->name]).$after;
    }
}
