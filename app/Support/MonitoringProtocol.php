<?php

namespace App\Support;

use App\Enums\AdverseEventType;
use App\Models\AiSystem;

/**
 * The monitoring protocol (C3): what may happen to an AI system in production.
 *
 * RF04 ties the adverse event types to the category of the system, so the
 * type is never free text. This is the one place that decides which types a
 * system accepts: the create form offers them and validation enforces them.
 *
 * @todo The protocol has not been defined yet, nor whether "category" means
 *       the EU AI Act risk tier or an application domain. Until it is, every
 *       type is allowed for every system. Map them here once it exists; the
 *       form and the validation need no change.
 */
class MonitoringProtocol
{
    /**
     * @return list<AdverseEventType>
     */
    public function eventTypesFor(AiSystem $aiSystem): array
    {
        return AdverseEventType::cases();
    }

    public function allows(AiSystem $aiSystem, AdverseEventType $type): bool
    {
        return in_array($type, $this->eventTypesFor($aiSystem), true);
    }

    /**
     * The types a system accepts, shaped for a select.
     *
     * @return list<array{value: string, label: string}>
     */
    public function eventTypeOptionsFor(AiSystem $aiSystem): array
    {
        return array_map(
            fn (AdverseEventType $type): array => ['value' => $type->value, 'label' => $type->label()],
            $this->eventTypesFor($aiSystem),
        );
    }
}
