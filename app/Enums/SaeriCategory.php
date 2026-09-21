<?php

namespace App\Enums;

use App\Concerns\EnumOptions;

/**
 * SAERI classification of a mitigation measure.
 *
 * The four top-level categories of the AI risk mitigation taxonomy by
 * Saeri et al.: Governance & Oversight, Technical & Security, Operational
 * Process and Transparency & Accountability. The mitigation catalog is
 * filtered by these categories.
 */
enum SaeriCategory: string
{
    use EnumOptions;

    case Governance = 'governance';
    case Technical = 'technical';
    case Process = 'process';
    case Transparency = 'transparency';

    /**
     * Get the full Portuguese name of the category.
     */
    public function label(): string
    {
        return match ($this) {
            self::Governance => 'Governança e Supervisão',
            self::Technical => 'Técnica e Segurança',
            self::Process => 'Processos Operacionais',
            self::Transparency => 'Transparência e Responsabilização',
        };
    }
}
