<?php

namespace App\Actions;

use App\Models\Evidence;
use App\Models\Link;
use Illuminate\Support\Arr;

/**
 * Register evidence for a link (RF03). Evidence is append only (0008): the
 * registration date is stamped here, and the moment it is stored is what a
 * re-verification compares with the last reversal (0018). It may report the
 * cost observed so far (RF07); the latest one that does is the link's
 * observed cost.
 */
class RecordEvidence
{
    /**
     * @param  array<string, mixed>  $attributes  The type, description and
     *                                            optional observed cost.
     */
    public function handle(Link $link, array $attributes): Evidence
    {
        return $link->evidence()->create([
            ...Arr::only($attributes, ['type', 'description', 'observed_cost']),
            'registration_date' => today(),
        ]);
    }
}
